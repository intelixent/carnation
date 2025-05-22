from fastapi import FastAPI, Request, Body
from fastapi.templating import Jinja2Templates
from fastapi.responses import JSONResponse
from fastapi.middleware.cors import CORSMiddleware
import pdfplumber
import pandas as pd
import re
import os
import json
from collections import defaultdict
import tempfile
import base64

app = FastAPI()

# Add CORS middleware
app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],  # In production, specify your Laravel app's origin
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)

templates = Jinja2Templates(directory="templates")

def extract_jackjones_2(pdf_path):
    
    with pdfplumber.open(pdf_path) as pdf:
        # Focus on page 3 which contains the item table
        page = pdf.pages[2]
        text = page.extract_text()
        
        # Find the table section starting with item details
        # Looking for the line pattern that begins with item numbers like "11 9010532001"
        lines = text.split('\n')
        
        # Find where the table data begins - it starts with item number pattern
        start_idx = 0
        for i, line in enumerate(lines):
            if re.match(r'^\s*\d{2}\s+\d{7,}', line):
                start_idx = i
                break
        
        # Extract only the data rows
        data_lines = []
        current_idx = start_idx
        print(lines);
        while current_idx < len(lines):
            line = lines[current_idx]
            
            # If this is a data row (starts with item number pattern)
            if re.match(r'^\s*\d{2}\s+\d{7,}', line):
                # Extract the components using regex pattern matching
                # The pattern captures the distinct parts of each row
                
                pattern = (
                    r'^(\d{2})\s+'             # Item number
                    r'(\d{7,})\s+'             # Article variant
                    r'(\d{9,})\s+\/\s+'        # ID
                    r'([A-Za-z]+)\s+'          # Color
                    r'(\d+\/\d+Y|\d+Y)\s+'     # Size
                    r'(\d+)\s+'                # Quantity
                    r'([A-Za-z]+\/[A-Za-z]+)\s+'  # Unit
                    r'([\d,.]+)\s+'            # IGST amount
                    r'([\d,.]+)\s+'            # IGST rate
                    r'([\d,.]+)\s+'            # MRP
                    r'(\d{11,})\s+'            # EAN Code
                    r'(\d{8})'                 # HSN
                )
                
                match = re.search(pattern, line)
                
                if match:
                    row_data = {
                        "Item": match.group(1),
                        "Article Variant": match.group(2),
                        "ID": match.group(3),
                        "Colour": match.group(4),
                        "Size": match.group(5),
                        "Quantity": match.group(6),
                        "Unit": match.group(7),
                        "IGST Amount": match.group(8),
                        "IGST Rate (%)": match.group(9),
                        "MRP": match.group(10),
                        "EAN Code": match.group(11),
                        "HSN": match.group(12)
                    }
                    data_lines.append(row_data)
                
            current_idx += 1
        
        # Alternative approach if regex matching fails
        if not data_lines:
            # Extract the relevant section manually
            table_section = '\n'.join(lines[start_idx:])
            
            # Find rows that start with item numbers (11, 12, etc.)
            item_rows = re.findall(r'^\s*(\d{2}\s+\d{7,}.*?)(?=^\s*\d{2}\s+\d{7,}|\Z)', 
                                 table_section, re.MULTILINE | re.DOTALL)
            
            for row in item_rows:
                # Clean up extra whitespace
                row = re.sub(r'\s+', ' ', row.strip())
                
                # Extract fields manually
                parts = row.split(' ')
                
                # Check if we have enough parts for a valid row
                if len(parts) >= 12:
                    # Handle the case where color might include spaces
                    color_idx = -1
                    for i, part in enumerate(parts):
                        if part.endswith('/'):
                            color_idx = i
                            break
                    
                    if color_idx > 0:
                        color = parts[color_idx+1]
                        
                        row_data = {
                            "Item": parts[0],
                            "Article Variant": parts[1],
                            "ID": parts[2],
                            "Colour": color,
                            "Size": parts[color_idx+2],
                            "Quantity": parts[color_idx+3],
                            "Unit": parts[color_idx+4],
                            "IGST Amount": parts[color_idx+5],
                            "IGST Rate (%)": parts[color_idx+6],
                            "MRP": parts[color_idx+7],
                            "EAN Code": parts[color_idx+8],
                            "HSN": parts[color_idx+9] if len(parts) > color_idx+9 else ""
                        }
                        data_lines.append(row_data)
        
        # Extract general order info
        order_info = {
            "PO Number": "",
            "PO Date": "",
            "Vendor": "",
            "Article": "",
            "Article Description": "",
            "Total Quantity": "",
            "Total Value": ""
        }
        
        # Look for PO Number and Date
        po_match = re.search(r'PO number / PO date\s+(\d+)\s+/\s+(\d{2}\.\d{2}\.\d{4})', text)
        if po_match:
            order_info["PO Number"] = po_match.group(1)
            order_info["PO Date"] = po_match.group(2)
            
        # Look for Article info
        article_match = re.search(r'(\d{7})\s+([\w\s]+)\s+\d{8}', text)
        if article_match:
            order_info["Article"] = article_match.group(1)
            order_info["Article Description"] = article_match.group(2)
            
        # Look for vendor info
        vendor_match = re.search(r'Vendor\s+([\w\s]+)', text)
        if vendor_match:
            order_info["Vendor"] = vendor_match.group(1)
            
        # Look for total quantity
        total_match = re.search(r'Total quantity\s+(\w+\/\w+)\s+(\d+)', text)
        if total_match:
            order_info["Total Quantity"] = total_match.group(2)
            
        # Look for total value
        value_match = re.search(r'Total Value\s+(\w+)\s+([\d,.]+)', text)
        if value_match:
            order_info["Total Value"] = value_match.group(2)
        
        return {
            "order_info": order_info,
            "line_items": data_lines
        }
def extract_jackjones(pdf_path):
    table_data = []
    sno = 1

    with pdfplumber.open(pdf_path) as pdf:
        page = pdf.pages[2]
        text = page.extract_text()
        raw_lines = text.split('\n')

        # Step 1: Merge lines until new item starts
        rows = []
        current = ""
        for line in raw_lines:
            line = line.strip()
            if re.match(r"^\d{2}\s", line):  # new row
                if current:
                    rows.append(current.strip())
                current = line
            else:
                current += " " + line
        if current:
            rows.append(current.strip())

        # Step 2: Use regex pattern to parse known structure
        pattern = re.compile(
            r'^(\d{2})\s+'                 # Item
            r'(\d+)\s+'                    # Article Variant
            r'(\d+)\s*/\s*([A-Za-z]+)\s+'  # ID / Colour split into ID + Colour
            r'([\d/]+Y?)\s+'               # Size
            r'(\d+)\s+'                    # Quantity
            r'(Nos/Pcs)\s+'                # Unit
            r'([\d.]+)\s+'                 # IGST Amount
            r'([\d.]+)\s+'                 # IGST Rate
            r'([\d,]+\.?\d*)\s+'           # MRP
            r'(\d+)\s+'                    # EAN Code
            r'(\d+)'                       # HSN
        )
        print(rows)
        for row in rows:
            match = pattern.match(row)
            if match:
                groups = match.groups()
                table_data.append({
                    "sno": sno,
                    "Item": groups[0],
                    "Article Variant": groups[1],
                    "ID / Colour": f"{groups[2]} / {groups[3]}",
                    "Size": groups[4],
                    "Quantity": groups[5],
                    "Unit": groups[6],
                    "IGST Amount": groups[7],
                    "IGST Rate (%)": groups[8],
                    "MRP": groups[9],
                    "EAN Code": groups[10],
                    "HSN": groups[11]
                })
                sno += 1
                
            else:
                print("⚠️ Skipped line (no match):", row)

    # Final output
    json_output = json.dumps(table_data, indent=4)
    print(json_output)

    return {
        "html_table": json_output
    }
def extract_clean_delivery_address(lines):
    capture = False
    address_lines = []

    for line in lines:
        line = line.strip()

        # Start capturing when 'Delivery Address:' is seen
        if 'Delivery Address:' in line:
            capture = True
            continue

        if capture:
            # Stop if 'GSTIN' or 'CIN' appears (end of block)
            if 'GSTIN' in line or 'CIN' in line or 'Communication address' in line:
                break
            address_lines.append(line)

    # Deduplicate repeated lines or fragments
    unique_lines = []
    seen = set()
    for line in address_lines:
        if line not in seen:
            unique_lines.append(line)
            seen.add(line)

    # Post-processing cleanup: remove duplicate company name
    full = ' '.join(unique_lines)
    full = re.sub(r'(BEST UNITED INDIA COMFORTS PVT LTD)[\s.]+\1', r'\1', full, flags=re.IGNORECASE)
    full = re.sub(r'\s+', ' ', full).strip()
    return full
def extract_clean_block(lines, start_key, stop_keywords):
    capture = False
    block_lines = []

    for line in lines:
        line = line.strip()

        if start_key in line:
            capture = True
            continue

        if capture:
            if any(stop in line for stop in stop_keywords):
                break
            block_lines.append(line)

    # Remove duplicate lines
    seen = set()
    unique_lines = []
    for line in block_lines:
        if line not in seen:
            seen.add(line)
            unique_lines.append(line)

    # Remove repeated company names if present
    full = ' '.join(unique_lines)
    full = re.sub(r'(BEST UNITED INDIA COMFORTS PVT LTD)[\s.]+\1', r'\1', full, flags=re.IGNORECASE)
    full = re.sub(r'\s+', ' ', full).strip()
    return full
def extract_address_block(lines, start_text, end_texts):
    capture = False
    collected = []

    for line in lines:
        line = line.strip()

        if start_text in line:
            capture = True
            continue

        if capture:
            if any(end in line for end in end_texts):
                break
            collected.append(line)

    # Combine lines and clean spaces
    return ' '.join(collected).replace(' ,', ',').strip()

def extract_jackjones_o(pdf_path):
    print(f"Starting extraction from: {pdf_path}")
    headers = [
        "Item", "Article Variant", "ID / Colour", "Size", "Quantity",
        "IGST", "IGST Rate (%)", "MRP", "EAN Code", "HSN"
    ]
    data_rows = []
    
    try:
        with pdfplumber.open(pdf_path) as pdf:
            print(f"PDF opened successfully. Total pages: {len(pdf.pages)}")
            
            # Initialize data dictionary
            podata = {}
            
            if len(pdf.pages) == 0:
                print("Error: PDF has no pages")
                return None
                
            # Process first page for PO details
            first_page = pdf.pages[0]
            print("Extracting text from first page")
            first_page_text = first_page.extract_text()
            lines = first_page_text.split('\n')
            print(f"First page has {len(lines)} lines of text")
            
            # Extract PO Number
            try:
                po_match = re.search(r'PO number\s*/\s*PO date\s*\n\s*(\d+)', first_page_text)
                if po_match:
                    podata['PO Number'] = po_match.group(1)
                    print(f"Extracted PO Number: {podata['PO Number']}")
                else:
                    print("Warning: PO Number not found")
                    podata['PO Number'] = "Not found"
            except Exception as e:
                print(f"Error extracting PO Number: {e}")
                podata['PO Number'] = "Error extracting"
            
            # Extract PO Date
            try:
                po_date_match = re.search(r'PO number\s*/\s*PO date\s*\n\s*\d+\s*/\s*([\d.]+)', first_page_text)
                if po_date_match:
                    podata['PO Date'] = po_date_match.group(1)
                    print(f"Extracted PO Date: {podata['PO Date']}")
                else:
                    print("Warning: PO Date not found")
                    podata['PO Date'] = "Not found"
            except Exception as e:
                print(f"Error extracting PO Date: {e}")
                podata['PO Date'] = "Error extracting"
            
            # Extract Goods Ready Date
            try:
                goods_ready_match = re.search(r'Goods Ready Date:\s*([\d.]+)', first_page_text)
                if goods_ready_match:
                    podata['Goods Ready Date'] = goods_ready_match.group(1)
                    print(f"Extracted Goods Ready Date: {podata['Goods Ready Date']}")
                else:
                    print("Warning: Goods Ready Date not found")
                    podata['Goods Ready Date'] = "Not found"
            except Exception as e:
                print(f"Error extracting Goods Ready Date: {e}")
                podata['Goods Ready Date'] = "Error extracting"

            # Extract tables from the first page
            print("Extracting tables from first page")
            try:
                tables = first_page.extract_tables()
                print(f"Number of tables found: {len(tables)}")
            except Exception as e:
                print(f"Error extracting tables: {e}")
                tables = []
            
            # Look for the address table
            address_table = None
            for i, table in enumerate(tables):
                try:
                    print(f"Analyzing Table {i} ({len(table)} rows)")
                    
                    # Check if this looks like an address table
                    flat_table = [str(cell) for row in table for cell in row if cell is not None]
                    address_keywords = ["Delivery Address", "Communication address"]
                    
                    if any(keyword in ' '.join(flat_table) for keyword in address_keywords):
                        address_table = table
                        print(f"Found address table (Table {i})")
                        break
                except Exception as e:
                    print(f"Error analyzing table {i}: {e}")
                    continue
            
            # If an address table was found, extract the addresses
            if address_table:
                print("Processing address table")
                try:
                    # Find the row with headers
                    header_row_index = None
                    for i, row in enumerate(address_table):
                        row_text = ' '.join([str(cell) for cell in row if cell])
                        if "Delivery Address" in row_text and "Communication address" in row_text:
                            header_row_index = i
                            print(f"Found address header row at index {i}")
                            break
                    
                    if header_row_index is not None and header_row_index + 1 < len(address_table):
                        # Get addresses from the row following the header row
                        address_row = address_table[header_row_index + 1]
                        print(f"Address row has {len(address_row)} columns")
                        
                        if len(address_row) >= 1 and address_row[0]:
                            # Extract delivery address
                            delivery_text = str(address_row[0])
                            print("Processing delivery address")
                            
                            # Remove GSTIN from delivery address
                            gstin_index = delivery_text.find("GSTIN")
                            if gstin_index > 0:
                                delivery_text = delivery_text[:gstin_index].strip()
                            
                            # Format the delivery address
                            delivery_lines = [line.strip() for line in delivery_text.split('\n')]
                            podata['Delivery Address'] = ' '.join(delivery_lines)
                            print(f"Extracted Delivery Address: {podata['Delivery Address']}")
                        
                        if len(address_row) >= 2 and address_row[1]:
                            # Extract communication address
                            comm_text = str(address_row[1])
                            print("Processing communication address")
                            
                            # Remove CIN from communication address
                            cin_index = comm_text.find("CIN")
                            if cin_index > 0:
                                comm_text = comm_text[:cin_index].strip()
                            
                            # Format the communication address
                            comm_lines = [line.strip() for line in comm_text.split('\n')]
                            podata['Communication Address'] = ' '.join(comm_lines)
                            print(f"Extracted Communication Address: {podata['Communication Address']}")
                except Exception as e:
                    print(f"Error processing address table: {e}")
            else:
                print("No address table found")
            
            # Extract GSTIN
            print("Searching for GSTIN")
            gstin_match = None
            
            # First check tables
            if address_table:
                for row in address_table:
                    for cell in row:
                        if cell and "GSTIN" in str(cell):
                            try:
                                gstin_match = re.search(r'GSTIN\.?:?\s*(\w+)', str(cell))
                                if gstin_match:
                                    podata['GSTIN'] = gstin_match.group(1)
                                    print(f"Extracted GSTIN from table: {podata['GSTIN']}")
                                    break
                            except Exception as e:
                                print(f"Error extracting GSTIN from table: {e}")
            
            # If not found in tables, try full text
            if 'GSTIN' not in podata:
                try:
                    gstin_patterns = [r'GSTIN\.?:?\s*(\w+)', r'GSTIN\.?\s*(\d+\w+)']
                    for pattern in gstin_patterns:
                        gstin_match = re.search(pattern, first_page_text)
                        if gstin_match:
                            podata['GSTIN'] = gstin_match.group(1)
                            print(f"Extracted GSTIN from text: {podata['GSTIN']}")
                            break
                    
                    if 'GSTIN' not in podata:
                        print("Warning: GSTIN not found")
                except Exception as e:
                    print(f"Error extracting GSTIN from text: {e}")
            
            # Extract CIN
            print("Searching for CIN")
            cin_match = None
            
            # First check tables
            if address_table:
                for row in address_table:
                    for cell in row:
                        if cell and "CIN" in str(cell):
                            try:
                                cin_match = re.search(r'CIN\s*:?\s*(\w+)', str(cell))
                                if cin_match:
                                    podata['CIN'] = cin_match.group(1)
                                    print(f"Extracted CIN from table: {podata['CIN']}")
                                    break
                            except Exception as e:
                                print(f"Error extracting CIN from table: {e}")
            
            # If not found in tables, try full text
            if 'CIN' not in podata:
                try:
                    cin_match = re.search(r'CIN\s*:?\s*(\w+)', first_page_text)
                    if cin_match:
                        podata['CIN'] = cin_match.group(1)
                        print(f"Extracted CIN from text: {podata['CIN']}")
                    else:
                        print("Warning: CIN not found")
                except Exception as e:
                    print(f"Error extracting CIN from text: {e}")

            # Extract MRP and VCP
            print("Searching for MRP and VCP")
            try:
                mrp_patterns = [
                    r'MRP to be:?\s*([^\n]+)',
                    r'MRP:?([\d,]+)/?-?'
                ]
                
                for pattern in mrp_patterns:
                    mrp_match = re.search(pattern, first_page_text)
                    if mrp_match:
                        podata['MRP'] = mrp_match.group(1).strip()
                        print(f"Extracted MRP: {podata['MRP']}")
                        break
                
                vcp_match = re.search(r'VCP to be\s*([^\n]+)', first_page_text)
                if vcp_match:
                    podata['VCP'] = vcp_match.group(1).strip()
                    print(f"Extracted VCP: {podata['VCP']}")
            except Exception as e:
                print(f"Error extracting MRP/VCP: {e}")

            # Extract Colors
            print("Searching for Colors")
            try:
                colors_match = re.search(r'Colors:?\s*(.*?)(?:\n|$)', first_page_text)
                if colors_match:
                    podata['Colors'] = colors_match.group(1).strip()
                    print(f"Extracted Colors: {podata['Colors']}")
            except Exception as e:
                print(f"Error extracting Colors: {e}")
           
            # Find article information - NEW APPROACH
            print("Searching for article information across pages")
            article_info = {}
            article_found = False
            article_header_page = -1
            article_header_line = -1
            
            # First, locate the article header
            for page_idx in range(min(len(pdf.pages), 10)):  # Limit to first 10 pages
                if article_header_page >= 0:
                    break
                    
                try:
                    page = pdf.pages[page_idx]
                    print(f"Checking page {page_idx+1} for article header")
                    page_text = page.extract_text()
                    page_lines = page_text.split('\n')
                    
                    for idx, line in enumerate(page_lines):
                        # Look for article description header markers
                        article_markers = ["ARTICLE Article description", "______________"]
                        if any(marker in line for marker in article_markers):
                            print(f"Found article header at line {idx} on page {page_idx+1}")
                            article_header_page = page_idx
                            article_header_line = idx
                            break
                except Exception as e:
                    print(f"Error checking page {page_idx+1} for article header: {e}")
            
            # If we found the article header, look for the article content on the next page
            if article_header_page >= 0:
                # Look at the current page first (if we're not at the end of the page)
                start_page = article_header_page
                
                for page_idx in range(start_page, min(start_page + 2, len(pdf.pages))):
                    if article_found:
                        break
                        
                    try:
                        page = pdf.pages[page_idx]
                        print(f"Checking page {page_idx+1} for article content")
                        page_text = page.extract_text()
                        page_lines = page_text.split('\n')
                        
                        # Start from the beginning if we're on a new page,
                        # otherwise start after the header line
                        start_line = 0 if page_idx > article_header_page else article_header_line + 1
                        
                        for idx in range(start_line, len(page_lines)):
                            line = page_lines[idx]
                            
                            # Check for article number and description pattern
                            article_match = re.match(r'^(\d{7})\s+(.+)', line)
                            if article_match:
                                print(f"Found article details at line {idx}")
                                
                                article_number = article_match.group(1)
                                first_desc_line = article_match.group(2)
                                
                                # Check if there are price digits at the end of the description
                                price_match = re.search(r'\s+\d+\.\d+\s+\/\s+EA', first_desc_line)
                                if price_match:
                                    # If we found price info, remove it from the description
                                    first_desc_line = first_desc_line[:price_match.start()]
                                
                                # Initialize full description with the first line
                                full_description = first_desc_line
                                
                                # Check if there's a second line for the description
                                next_line_idx = idx + 1
                                if next_line_idx < len(page_lines):
                                    next_line = page_lines[next_line_idx].strip()
                                    
                                    # If the next line doesn't start with a number (except for price info),
                                    # it's likely a continuation of the description
                                    if not re.match(r'^\d{8,}', next_line) and not re.match(r'^\d+\.\d+\s+\/\s+EA', next_line):
                                        # This is a continuation of the description
                                        second_desc_line = next_line
                                        
                                        # Check if there are price digits in this line as well
                                        price_match = re.search(r'\s+\d+\.\d+\s+\/\s+EA', second_desc_line)
                                        if price_match:
                                            second_desc_line = second_desc_line[:price_match.start()]
                                        
                                        full_description += " " + second_desc_line
                                        next_line_idx += 1  # Move to the next line after the description
                                
                                # Set the full description
                                article_info["ARTICLE"] = article_number
                                article_info["Article description"] = full_description.strip()
                                print(f"Extracted Article: {article_number} - {full_description}")
                                
                                # Now look for the customs code in the next line(s)
                                customs_code_idx = next_line_idx
                                if customs_code_idx < len(page_lines):
                                    customs_line = page_lines[customs_code_idx].strip()
                                    
                                    # Look for a line that contains only digits (customs code)
                                    if re.match(r'^\d{8,}$', customs_line):
                                        article_info["Customs code"] = customs_line
                                        print(f"Extracted Customs code: {article_info['Customs code']}")
                                        customs_code_idx += 1
                                    else:
                                        # If we didn't find it immediately, check the next few lines
                                        for i in range(1, 3):  # Look at up to 3 more lines
                                            if customs_code_idx + i >= len(page_lines):
                                                break
                                                
                                            customs_line = page_lines[customs_code_idx + i].strip()
                                            if re.match(r'^\d{8,}$', customs_line):
                                                article_info["Customs code"] = customs_line
                                                print(f"Extracted Customs code: {article_info['Customs code']}")
                                                customs_code_idx = customs_code_idx + i + 1
                                                break
                                
                                # Continue with the other fields, starting after the customs code
                                start_idx = customs_code_idx
                                
                                # Extract fabric composition
                                if start_idx < len(page_lines):
                                    fabric_line = page_lines[start_idx].strip()
                                    if "%" in fabric_line:
                                        article_info["Fabric composition"] = fabric_line
                                        print(f"Extracted Fabric composition: {article_info['Fabric composition']}")
                                        start_idx += 1
                                
                                # Extract construction type
                                if start_idx < len(page_lines):
                                    const_line = page_lines[start_idx].strip()
                                    #if const_line and "Knit" in const_line:
                                    article_info["Construction type"] = const_line
                                    print(f"Extracted Construction type: {article_info['Construction type']}")
                                    start_idx += 1
                                
                                # Extract gender
                                if start_idx < len(page_lines):
                                    gender_line = page_lines[start_idx].strip()
                                    #if gender_line in ["Male", "Female", "Unisex"]:
                                    article_info["Gender"] = gender_line
                                    print(f"Extracted Gender: {article_info['Gender']}")
                                    start_idx += 1
                                
                                # Extract article group
                                if start_idx < len(page_lines):
                                    group_line = page_lines[start_idx].strip()
                                    # Check if this line follows the pattern of article groups (often in ALL-CAPS with hyphens)
                                    if group_line and (group_line.isupper() or '-' in group_line) and not re.match(r'^\d', group_line):
                                        # This is likely an article group based on format, not specific content
                                        article_info["Article group"] = group_line
                                        print(f"Extracted Article group: {article_info['Article group']}")
                                        start_idx += 1
                                
                                # Extract country of origin (usually not explicitly stated in the examples)
                                article_info["Country of origin"] = "India"  # Default assumption
                                
                                # Try to extract pricing from the lines containing price information
                                try:
                                    price_pattern = re.search(r"(\d+\.\d+)\s*/\s*(\w+)\s+(\d+\.\d+)\s+(\w+/\w+)\s+([\d,]+\.\d+)\s+([A-Z]+)", page_text)
                                    if price_pattern:
                                        article_info["Price per unit"] = price_pattern.group(1) + " / " + price_pattern.group(2)
                                        article_info["Total unit"] = price_pattern.group(3) + " " + price_pattern.group(4)
                                        article_info["Net Value"] = price_pattern.group(5)
                                        article_info["Currency"] = price_pattern.group(6)
                                        print(f"Extracted price information: {price_pattern.group(0)}")
                                except Exception as e:
                                    print(f"Error extracting price information: {e}")
                                
                                article_found = True
                                break
                    except Exception as e:
                        print(f"Error processing page {page_idx+1} for article info: {e}")
            
            if not article_found:
                print("Warning: No article information found")
            
            # Process items from all pages
            print("Scanning for item rows across all pages")
            all_items = []
            
            for page_idx in range(len(pdf.pages)):
                try:
                    page = pdf.pages[page_idx]
                    text = page.extract_text()
                    lines = text.split('\n')
                    print(f"Scanning page {page_idx+1} with {len(lines)} lines for items")
                    
                    i = 0
                    while i < len(lines) - 1:
                        line = lines[i]
                        
                        # Match the item row pattern
                        # More flexible pattern to catch variations
                        try:
                            # item_match = re.match(
                            #     r'^(\d+)\s+(\d+)\s+(\d+)\s+/\s+(.*?)\s+(\d+)\s+(Nos/Pcs|Nos|Pcs)\s+([\d,]+\.?\d*)\s+([\d.]+)\s+([\d,]+\.?\d*)\s+(\d+)',
                            #     line
                            # )
                            item_match = re.match(
                                r'^(\d+)\s+(\d+)\s+(\d+)\s+/\s+([\w/]+)\s+(\d+)\s+(Nos/Pcs|Nos|Pcs)\s+([\d,]+\.\d+)\s+([\d.]+)\s+([\d,]+\.\d+)\s+(\d{11})\s+(\d+)',
                                line
                            )
                            
                            if item_match:
                                print(f"Found item row at line {i} on page {page_idx+1}")
                                item_number = item_match.group(1)
                                article_variant = item_match.group(2)
                                variant_id = item_match.group(3)
                                
                                # Check for next line with additional information
                                if i + 1 < len(lines):
                                    next_line = lines[i + 1]
                                    print(f"Next line: {next_line}")
                                    
                                    # Check if the next line has the color and EAN suffix
                                    color_match = re.match(r'^(.*?)(\d+)$', next_line.strip())
                                    
                                    if color_match:
                                        color = color_match.group(1).strip()
                                        ean_suffix = color_match.group(2)
                                        print(f"Extracted color: '{color}' and EAN suffix: {ean_suffix}")
                                        
                                        # Get size
                                        size = item_match.group(4).strip()
                                        
                                        # Get remaining fields
                                        quantity_value = item_match.group(5)
                                        nos_pcs = item_match.group(6)
                                        igst = item_match.group(7)
                                        igst_rate = item_match.group(8)
                                        mrp = item_match.group(9)
                                        ean_partial = item_match.group(10)
                                        
                                        # HSN code
                                        hsn = ""
                                        if len(item_match.groups()) > 10:
                                            hsn = item_match.group(11)
                                        else:
                                            # Try to extract HSN from the end of next_line
                                            hsn_match = re.search(r'(\d{8})$', next_line)
                                            if hsn_match:
                                                hsn = hsn_match.group(1)
                                                print(f"Extracted HSN from next line: {hsn}")
                                            # If HSN not in next line, try to find it elsewhere on the page
                                            elif "HSN" in text:
                                                hsn_line_match = re.search(r'HSN[^\d]*(\d{8})', text)
                                                if hsn_line_match:
                                                    hsn = hsn_line_match.group(1)
                                                    print(f"Extracted HSN from page text: {hsn}")
                                        
                                        full_ean = ean_partial + ean_suffix
                                        quantity = f"{quantity_value} {nos_pcs}"
                                        id_colour = f"{variant_id}/{color.strip()}"
                                        
                                        row = {
                                            "item_sno": item_number,
                                            "article_number": article_variant,
                                            "artcicle_id_color": id_colour,
                                            "size_years": size,
                                            "quatity_uom": quantity,
                                            "igst_taxable_value": igst,
                                            "igst_percentage": igst_rate,
                                            "mrp": mrp,
                                            "ean_code": full_ean,
                                            "hsn_code": hsn
                                        }
                                        
                                        data_rows.append(row)
                                        print(f"Added item row: {row}")
                                        i += 2  # Skip the next line
                                        continue
                        except Exception as e:
                            print(f"Error parsing item row at line {i}: {e}")
                        
                        # Check for totals information
                        try:
                            total_match = re.search(r'Total Value\s+([A-Z]+)\s+([\d,]+\.\d+)', line)
                            if total_match:
                                podata['Total_Currency'] = total_match.group(1)
                                podata['Total_Value'] = total_match.group(2)
                                print(f"Extracted Total Value: {podata['Total_Currency']} {podata['Total_Value']}")
                            
                            total_igst_match = re.search(r'Total IGST\s+([A-Z]+)\s+([\d,]+\.\d+)', line)
                            if total_igst_match:
                                podata['Total_IGST_Currency'] = total_igst_match.group(1)
                                podata['Total_IGST'] = total_igst_match.group(2)
                                print(f"Extracted Total IGST: {podata['Total_IGST_Currency']} {podata['Total_IGST']}")
                            
                            total_incl_match = re.search(r'Total Value IncTax\s+([\d,]+\.\d+)', line)
                            if total_incl_match:
                                podata['Total_Value_IncTax'] = total_incl_match.group(1)
                                print(f"Extracted Total Value IncTax: {podata['Total_Value_IncTax']}")
                            
                            total_qty_match = re.search(r'Total quantity\s+(\w+\/\w+)\s+(\d+)', line)
                            if total_qty_match:
                                podata['Total_Quantity_UOM'] = total_qty_match.group(1)
                                podata['Total_Quantity'] = total_qty_match.group(2)
                                print(f"Extracted Total Quantity: {podata['Total_Quantity']} {podata['Total_Quantity_UOM']}")
                        except Exception as e:
                            print(f"Error extracting totals at line {i}: {e}")
                        
                        i += 1
                except Exception as e:
                    print(f"Error processing page {page_idx+1} for items: {e}")
            
            print(f"Extraction complete. Found {len(data_rows)} item rows")
            
            final_result = {
                "po_details": podata,
                "article_info": article_info,
                "po_items": data_rows
            }
            
            print("Final result structure:")
            print(f"- PO details: {len(podata)} fields")
            print(f"- Article info: {len(article_info)} fields")
            print(f"- PO items: {len(data_rows)} rows")
            
            return final_result
            
    except FileNotFoundError:
        print(f"Error: PDF file not found at {pdf_path}")
        return None
    except Exception as e:
        print(f"Unexpected error during extraction: {e}")
        import traceback
        traceback.print_exc()
        return None


def extract_jackjones_3(pdf_path):
    headers = [
        "Item", "Article Variant", "ID / Colour", "Size", "Quantity",
        "IGST", "IGST Rate (%)", "MRP", "EAN Code", "HSN"
    ]
    data_rows = []
    
    with pdfplumber.open(pdf_path) as pdf:
        # Extract Page 3 (index 2)
        page = pdf.pages[2]
        text = page.extract_text()
        # Split text into lines
        raw_lines  = text.split('\n')
        lines = []
        i = 0
        while i < len(raw_lines):
            line = raw_lines[i].strip()
            if line.endswith("/"):
                # Combine with next line if available
                if i + 1 < len(raw_lines):
                    combined_line = line + " " + raw_lines[i + 1].strip()
                    lines.append(combined_line)
                    i += 2  # skip next line
                else:
                    lines.append(line)
                    i += 1
            else:
                lines.append(line)
                i += 1
        print(lines)
        # Filter lines that look like data rows (start with 11, 12, etc.)
        data_lines = [line for line in lines if re.match(r'^\s*\d{2}\s', line)]
        
        # Define table headers
        headers = [
            "sno" , "Item", "Article Variant", "ID / Colour", "Size", "Quantity", "Unit", 
            "IGST Amount", "IGST Rate (%)", "MRP", "EAN Code", "HSN"
        ]

        # Prepare rows
        table_data = []
        for line in data_lines:
            
            # Split line by 2+ spaces
            parts = re.split(r'\s{1,}', line.strip())
            print(len(parts))
            # Fix or skip incomplete rows
            if len(parts) < len(headers):
                continue
           
            # Create dict row
            row_dict = dict(zip(headers, parts))
            
            table_data.append(row_dict)
        
        # Export to JSON
        json_output = json.dumps(table_data, indent=4)

        # Print or save
        print(json_output)
        
        return {
            "html_table": json_output
        }
    return None

def extract_skechers(pdf_path):
    print(f"[DEBUG] Opening PDF: {pdf_path}")
    po_details = {
        'order_no': None,
        'order_date': None,
        'customer_name': None,
        'customer_address': None,
        'customer_gstin': None,
        'ship_to_address': []
    }
    po_items = []

    with pdfplumber.open(pdf_path) as pdf:
        page = pdf.pages[0]
        text = page.extract_text() or ""
        print("[DEBUG] Extracted text length:", len(text))

        # Order No
        m = re.search(r'Purchase Order No\.?\s*:\s*([^\s]+)', text)
        print("[DEBUG] PO No match:", m)
        if m:
            po_details['order_no'] = m.group(1).strip()
            print("[DEBUG] Parsed order_no =", po_details['order_no'])

        # Order Date
        m = re.search(r'Date\s*:\s*(\d{1,2}/\d{1,2}/\d{2,4})', text)
        print("[DEBUG] Date match:", m)
        if m:
            po_details['order_date'] = m.group(1).strip()
            print("[DEBUG] Parsed order_date =", po_details['order_date'])

        # Customer Name
        m = re.search(r'Customer Name\s*:\s*([^\n]+)', text)
        print("[DEBUG] Cust Name match:", m)
        if m:
            po_details['customer_name'] = m.group(1).strip()
            print("[DEBUG] Parsed customer_name =", po_details['customer_name'])

        # Customer Address
        addr_match = re.search(r'Customer Address\s*:\s*([\s\S]+?)Customer GSTIN', text)
        print("[DEBUG] Address block match:", bool(addr_match))
        if addr_match:
            addr = addr_match.group(1).strip().replace('Customer Address :', '').strip()
            po_details['customer_address'] = ' '.join(addr.splitlines())
            print("[DEBUG] Parsed customer_address =", po_details['customer_address'])

        # Customer GSTIN
        m = re.search(r'Customer GSTIN\s*:\s*([^\n]+)', text)
        print("[DEBUG] GSTIN match:", m)
        if m:
            po_details['customer_gstin'] = m.group(1).strip()
            print("[DEBUG] Parsed customer_gstin =", po_details['customer_gstin'])

        # Ship to address from table rows
        for table in page.extract_tables():
            for row in table:
                # Check if any cell in the row contains 'Shipment Type'
                if any(cell and 'Shipment Type' in str(cell) for cell in row):
                    addr_cell = row[6] if row else None
                    address_lines = []
                    if addr_cell:
                        # Split cell content into lines and clean up
                        for ln in str(addr_cell).split('\n'):
                            ln = ln.strip()
                            if ln:
                                address_lines.append(ln)
                    po_details['ship_to_address'] = address_lines
                    print(f"[DEBUG] Parsed ship_to_address = {po_details['ship_to_address']}")
                    break  # stop after processing the first matching row
            if po_details['ship_to_address']:
                break  # exit loop once address is found

        # locate main table
        main_table = None
        header_idx = None
        for table in page.extract_tables():
            for idx, row in enumerate(table):
                cells = [str(c) for c in (row or [])]
                if any('Sr' in c for c in cells) and any('Style No' in c for c in cells):
                    main_table = table
                    header_idx = idx
                    print(f"[DEBUG] Found main_table at row {header_idx}")
                    break
            if main_table:
                break

        if not main_table:
            print("[DEBUG] No main table found.")
        else:
            raw_headers = main_table[header_idx]
            headers = [re.sub(r"[\n\r]+", " ", h or '').strip() for h in raw_headers]
            print("[DEBUG] Headers:", headers)

            for row in main_table[header_idx + 1:]:
                # skip entirely blank rows
                if not any(cell and cell.strip() for cell in row):
                    print("[DEBUG] Skipping blank row")
                    continue

                # enforce that Sr. No. (second cell) is numeric
                sr = (row[1] or '').strip()
                if not sr.isdigit():
                    print(f"[DEBUG] Skipping non‐item row (Sr. No. = '{sr}')")
                    continue

                # skip the grand‐total rows
                if 'Total' in sr or 'TOTAL' in sr:
                    print(f"[DEBUG] Skipping total row: {row}")
                    continue

                # build the item dict
                item = {}
                for col_idx, header in enumerate(headers):
                    val = row[col_idx] if col_idx < len(row) and row[col_idx] else ''
                    item[header] = val.strip() if isinstance(val, str) else val

                # finally only append if we really see a Style No
                if item.get('Style No') or item.get('Style No.'):
                    po_items.append(item)
                    print(f"[DEBUG] Appended item: Sr={sr}, Style No={item.get('Style No') or item.get('Style No.')}, QTY={item.get('QTY IN PCS')}")
                else:
                    print("[DEBUG] No Style No in row; skipping")

    print(f"[DEBUG] Total items extracted: {len(po_items)}")
    return {
        'po_details': po_details,
        'po_items': po_items
    }

def extract_puma(pdf_path):
    results = {}
    
    # Initialize dictionaries for each section
    po_details = {}
    customer_details = {}
    article_info = {}
    po_items = []
    
    STATIC_CUSTOMER_ADDRESS = """Puma Sports India Pvt
        Ground floor 496,Next to Hewlett
        Packard Service Gate,
        Mahadevapura Main Road,
        Mahadevapura
        Bangalore
        Karnataka
        560048
        IN"""

    with pdfplumber.open(pdf_path) as pdf:
        # Process first page for PO details and customer information
        page = pdf.pages[0]
        text = page.extract_text()
        lines = text.split('\n')
        
        # Extract PO details
        for line in lines:
            if "PO Number" in line and "PO Release Date" in line:
                po_header = line.strip()
                continue
                
            if re.search(r'^\d{10}\s+\d{4}-\d{2}-\d{2}\s+\d{4}-\d{2}-\d{2}', line):
                parts = line.split()
                po_details["po_number"] = parts[0].strip()
                po_details["po_release_date"] = parts[1].strip()
                po_details["po_ehd"] = parts[2].strip()
                po_details["customer_address"] = STATIC_CUSTOMER_ADDRESS
                print(f"DEBUG: Extracted PO details - Number: {po_details['po_number']}, Release Date: {po_details['po_release_date']}, EHD: {po_details['po_ehd']}")
                break
        
        # Process second page for article info and PO items
        if len(pdf.pages) > 1:
            page = pdf.pages[1]
            text = page.extract_text()
            lines = text.split('\n')
            
            # Try to extract table data directly first
            tables = page.extract_tables()
            
            ship_to_address = []
            ship_to_found = False
            
            # Try to extract from tables first
            for i, table in enumerate(tables):
                for row in table:
                    if row and "Ship To" in str(row[0]):
                        ship_to_found = True
                        print(f"DEBUG: Found 'Ship To' in table row: {row}")
                        # If Ship To is in a table, the address might be in subsequent cells/rows
                        for j in range(1, len(row)):
                            if row[j] and row[j].strip():
                                ship_to_address.append(row[j].strip())
                    elif ship_to_found and row and row[0] is None and len(row) > 1:
                        # Possible continuation of address in subsequent rows
                        if row[1] and row[1].strip():
                            ship_to_address.append(row[1].strip())
                    elif ship_to_found and row and "Ship Mode" in str(row[0]):
                        # End of ship to section
                        ship_to_found = False
            
            # If table extraction didn't work, try text-based extraction
            if not ship_to_address:
                print("DEBUG: Table extraction failed, trying text-based extraction")
                in_ship_to_section = False
                ship_to_marker_found = False
                
                for line in lines:
                    if "Aggregated Production View" in line:
                        in_ship_to_section = True
                        print("DEBUG: Found 'Aggregated Production View' - entering ship to section")
                        continue
                    
                    if in_ship_to_section and "Ship To" in line:
                        ship_to_marker_found = True
                        print(f"DEBUG: Found 'Ship To' line: {line}")
                        # Check if there's content after "Ship To" on the same line
                        address_part = line.split("Ship To")[-1].strip()
                        if address_part:
                            ship_to_address.append(address_part)
                        continue
                    
                    if ship_to_marker_found and not "Ship Mode" in line:
                        # This could be a continuation of the address
                        if line.strip() and not any(keyword in line for keyword in ["Ultimate Cust", "Article Number", "Size International"]):
                            ship_to_address.append(line.strip())
                            print(f"DEBUG: Added address line: {line.strip()}")
                    
                    if ship_to_marker_found and "Ship Mode" in line:
                        print("DEBUG: Found 'Ship Mode' - ending ship to extraction")
                        break
            
            # Set delivery address (Ship To address) - this is different from customer address
            if ship_to_address:
                delivery_address_str = "\n".join(ship_to_address)
                po_details["delivery_address"] = delivery_address_str
                print(f"DEBUG: Delivery address extracted: {delivery_address_str}")
                po_details["customer_address"] = STATIC_CUSTOMER_ADDRESS
                
                # Update customer_details with Ship To address (for backward compatibility)
                customer_details["address"] = delivery_address_str
            else:
                po_details["delivery_address"] = ""
                customer_details["address"] = ""
                print("DEBUG: No delivery address found")
                        
            for i, line in enumerate(lines):
                if "Article Number" in line and "Style Description" in line and "Color" in line:
                    if i + 1 < len(lines):
                        article_line = lines[i + 1]
                        print(f"DEBUG: Raw article line: {article_line}")
                        
                        article_data = re.split(r'\s+', article_line.strip())
                        print(f"DEBUG: Processed article data: {article_data}")
                        
                        if len(article_data) >= 4:
                            article_info["article_number"] = article_data[0]
                            
                            # Find PUMA position
                            puma_index = next((idx for idx, word in enumerate(article_data) 
                                            if "PUMA" in word), None)
                            
                            if puma_index and puma_index > 1:
                                # Style Description = everything between article number and PUMA
                                article_info["style_description"] = " ".join(article_data[1:puma_index])
                                
                                # Color = PUMA + next word
                                color_parts = article_data[puma_index:puma_index+2]
                                article_info["color"] = " ".join(color_parts)
                                
                                # Product Character = remaining parts
                                product_parts = article_data[puma_index+2:]
                                article_info["product_character"] = " ".join(product_parts)
                                
                                print(f"DEBUG: Correct article info: {article_info}")
                                break
                            else:
                                # Fallback if PUMA not found
                                article_info["style_description"] = " ".join(article_data[1:-2])
                                article_info["color"] = article_data[-2]
                                article_info["product_character"] = article_data[-1]
            
            # Extract PO items and additional fields (Pack Factor, SKU/Line No, Incoterm, Named Place)
            size_row = None
            quantity_row = None
            price_row = None
            pack_factor_row = None
            sku_line_row = None
            incoterm_row = None
            named_place_row = None
            
            for i, line in enumerate(lines):
                if "Size International" in line:
                    size_row = line.replace("Size International", "").strip().split()
                    print(f"DEBUG: Size row extracted: {size_row}")
                if size_row and re.search(r"Total Size Qty EACH|Quantity EACH", line):
                    quantity_parts = re.split(r"Total Size Qty EACH|Quantity EACH", line)
                    if len(quantity_parts) > 1:
                        quantity_row = quantity_parts[1].strip().split()
                        print(f"DEBUG: Quantity row extracted: {quantity_row}")
                if size_row and "Unit Price INR" in line:
                    price_parts = line.split("Unit Price INR")
                    if len(price_parts) > 1:
                        price_row = price_parts[1].strip().split()
                        print(f"DEBUG: Price row extracted: {price_row}")
                if size_row and "Pack Factor" in line:
                    pack_parts = line.split("Pack Factor")
                    if len(pack_parts) > 1:
                        pack_factor_row = pack_parts[1].strip().split()
                        print(f"DEBUG: Pack factor row extracted: {pack_factor_row}")
                if size_row and "SKU/Line No" in line:
                    sku_parts = line.split("SKU/Line No.")
                    if len(sku_parts) > 1:
                        sku_line_row = sku_parts[1].strip().split()
                        print(f"DEBUG: SKU/Line row extracted: {sku_line_row}")
                if size_row and "Incoterm" in line:
                    incoterm_parts = line.split("Incoterm")
                    if len(incoterm_parts) > 1:
                        incoterm_row = incoterm_parts[1].strip().split()
                        print(f"DEBUG: Incoterm row extracted: {incoterm_row}")
                if size_row and "Named Place" in line:
                    named_place_parts = line.split("Named Place")
                    if len(named_place_parts) > 1:
                        named_place_row = named_place_parts[1].strip().split()
                        print(f"DEBUG: Named place row extracted: {named_place_row}")
            
            # If we don't find the explicit rows, try to find them in the bottom part of PDF
            if not pack_factor_row or not sku_line_row or not incoterm_row or not named_place_row:
                print("DEBUG: Some rows missing, searching in bottom part of PDF")
                for i, line in enumerate(lines):
                    if "Pack Factor" in line and i + 1 < len(lines):
                        potential_values = lines[i + 1].strip().split()
                        if potential_values and len(potential_values) >= len(size_row or []):
                            pack_factor_row = potential_values
                            print(f"DEBUG: Pack factor row found in bottom: {pack_factor_row}")
                    if "SKU/Line No" in line and i + 1 < len(lines):
                        potential_values = lines[i + 1].strip().split()
                        if potential_values and len(potential_values) >= len(size_row or []):
                            sku_line_row = potential_values
                            print(f"DEBUG: SKU/Line row found in bottom: {sku_line_row}")
                    if "Incoterm" in line and i + 1 < len(lines):
                        potential_values = lines[i + 1].strip().split()
                        if potential_values and len(potential_values) >= len(size_row or []):
                            incoterm_row = potential_values
                            print(f"DEBUG: Incoterm row found in bottom: {incoterm_row}")
                    if "Named Place" in line and i + 1 < len(lines):
                        potential_values = lines[i + 1].strip().split()
                        if potential_values and len(potential_values) >= len(size_row or []):
                            named_place_row = potential_values
                            print(f"DEBUG: Named place row found in bottom: {named_place_row}")
            
            # Create PO items from size and quantity data
            if size_row and quantity_row:
                print(f"DEBUG: Creating {min(len(size_row), len(quantity_row))} PO items")
                for i in range(min(len(size_row), len(quantity_row))):
                    item = {
                        "size": size_row[i],
                        "quantity": quantity_row[i],
                    }
                    if price_row and i < len(price_row):
                        item["unit_price"] = price_row[i]
                    if pack_factor_row and i < len(pack_factor_row):
                        item["pack_factor"] = pack_factor_row[i]
                    if sku_line_row and i < len(sku_line_row):
                        item["sku_line_no"] = sku_line_row[i]
                    if incoterm_row and i < len(incoterm_row):
                        item["incoterm"] = incoterm_row[i]
                    if named_place_row and i < len(named_place_row):
                        item["named_place"] = named_place_row[i]
                    po_items.append(item)
                    print(f"DEBUG: Created PO item {i+1}: {item}")

    # Create final result structure
    results = {
        "po_details": po_details,
        "article_info": article_info,
        "po_items": po_items,
        "customer_details": {
            "address": STATIC_CUSTOMER_ADDRESS 
        }
    }

    return results

def extract_ship_to_address_simple(text):
    """
    Extract ship to address using a simpler, more reliable approach
    """
    print("\n--- SIMPLE SHIP TO EXTRACTION ---")
    
    # Find the position of "Ship To"
    ship_to_pos = text.find("Ship To")
    if ship_to_pos == -1:
        print("'Ship To' not found")
        return None
    
    # Find the position of "Bill To" (this marks the end of ship to section)
    bill_to_pos = text.find("Bill To", ship_to_pos)
    if bill_to_pos == -1:
        print("'Bill To' not found after 'Ship To'")
        return None
    
    # Extract the text between "Ship To" and "Bill To"
    ship_to_section = text[ship_to_pos:bill_to_pos].strip()
    print(f"Ship To section: {ship_to_section}")
    
    # Split into lines and clean up
    lines = ship_to_section.split('\n')
    
    # Remove the "Ship To." line itself and any empty lines
    address_lines = []
    for line in lines:
        line = line.strip()
        if line and not line.startswith("Ship To"):
            # Skip lines that contain commas at the start (these are often formatting artifacts)
            if not line.startswith(','):
                address_lines.append(line)
    
    # Extract GSTIN if present and remove it from address
    gstin = None
    clean_address_lines = []
    
    for line in address_lines:
        if "GSTIN:" in line:
            gstin_match = re.search(r'GSTIN:\s*([A-Z0-9]+)', line)
            if gstin_match:
                gstin = gstin_match.group(1)
                # Remove GSTIN from the line
                line_without_gstin = line.split("GSTIN:")[0].strip()
                if line_without_gstin:
                    clean_address_lines.append(line_without_gstin)
        else:
            clean_address_lines.append(line)
    
    return {
        "address_lines": clean_address_lines,
        "gstin": gstin
    }

def extract_benetton(pdf_path):
    print("Starting extraction from:", pdf_path)
    results = {}
    
    try:
        with pdfplumber.open(pdf_path) as pdf:
            print(f"PDF opened successfully with {len(pdf.pages)} pages")
            
            # Extract all tables from every page
            all_tables = []
            for i, page in enumerate(pdf.pages):
                print(f"\nProcessing Page {i+1} tables...")
                tables = page.extract_tables({
                    "vertical_strategy": "lines",
                    "horizontal_strategy": "lines",
                    "explicit_vertical_lines": page.curves + page.edges,
                    "explicit_horizontal_lines": page.curves + page.edges,
                })
                
                for table_num, table in enumerate(tables):
                    print(f"\nTable {table_num+1} on page {i+1}:")
                    for row_num, row in enumerate(table):
                        print(f"Row {row_num}: {row}")
                    all_tables.append(table)
            
            # Store raw tables for template to handle
            results["raw_tables"] = all_tables
            
            # Extract text from all pages
            text = ""
            for page in pdf.pages:
                page_text = page.extract_text()
                text += page_text + "\n"
                print(f"\n--- Page {pdf.pages.index(page) + 1} Text Preview ---")
                print(page_text[:500] + "..." if len(page_text) > 500 else page_text)
            
            print("\n--- DEBUGGING: Looking for 'Ship To' in text ---")
            ship_to_index = text.find("Ship To")
            if ship_to_index >= 0:
                print(f"Found 'Ship To' at position {ship_to_index}")
                # Print the text context around "Ship To"
                context_start = max(0, ship_to_index - 50)
                context_end = min(len(text), ship_to_index + 300)
                print(f"Context around 'Ship To': \n{text[context_start:context_end]}")
            else:
                print("'Ship To' not found in text!")
            
            # Original extraction logic for order details
            order_no_match = re.search(r'Order No:?\s*(\d+)', text)
            order_date_match = re.search(r'Order Date:?\s*(\d{2}\.\d{2}\.\d{4})', text)
            delivery_date_match = re.search(r'Delivery Date:?\s*(\d{2}\.\d{2}\.\d{4})', text)
            season_match = re.search(r'Season:?\s*([A-Za-z\s]+\d{4})', text)
            
            results.update({
                "order_no": order_no_match.group(1) if order_no_match else "",
                "order_date": order_date_match.group(1) if order_date_match else "",
                "delivery_date": delivery_date_match.group(1) if delivery_date_match else "",
                "season": season_match.group(1) if season_match else ""
            })
            
            # Try multiple patterns for Ship To address extraction with debugging
            print("\n--- DEBUGGING: Trying different Ship To patterns ---")
            
            # Pattern 1: Original pattern
            ship_to_pattern1 = r'Ship To\.\s*\n((?:.*\n){1,7}?)(?:Bill To\.|GSTIN:)'
            ship_to_match1 = re.search(ship_to_pattern1, text)
            print(f"Pattern 1 match result: {ship_to_match1 is not None}")
            
            # Pattern 2: More flexible pattern
            ship_to_pattern2 = r'Ship To\.?\s*[,.\n]((?:.*\n){1,7}?)(?:Bill To\.?|GSTIN:)'
            ship_to_match2 = re.search(ship_to_pattern2, text)
            print(f"Pattern 2 match result: {ship_to_match2 is not None}")
            
            # Pattern 3: Very simple pattern
            ship_to_pattern3 = r'Ship To[^B]*Bill To'
            ship_to_match3 = re.search(ship_to_pattern3, text, re.DOTALL)
            print(f"Pattern 3 match result: {ship_to_match3 is not None}")
            
            # Use the first successful match
            ship_to_match = ship_to_match1 or ship_to_match2 or ship_to_match3
            
            if ship_to_match:
                # Process the ship to address into an array of lines
                ship_to_text = ship_to_match.group(1).strip() if (ship_to_match1 or ship_to_match2) else ship_to_match.group(0).replace("Ship To", "").replace("Bill To", "").strip()
                print(f"\nRaw ship to text: {ship_to_text}")
                
                ship_to_lines = [line.strip() for line in ship_to_text.split('\n') if line.strip()]
                print(f"Parsed ship to lines: {ship_to_lines}")
                
                # Extract GSTIN separately if it's in the ship to address
                gstin = None
                for i, line in enumerate(ship_to_lines):
                    if "GSTIN:" in line:
                        gstin_match = re.search(r'GSTIN:\s*([A-Z0-9]+)', line)
                        if gstin_match:
                            gstin = gstin_match.group(1)
                            # Remove GSTIN from the address line
                            ship_to_lines[i] = line.split("GSTIN:")[0].strip()
                            # If the line is now empty, remove it
                            if not ship_to_lines[i]:
                                ship_to_lines.pop(i)
                            print(f"Extracted GSTIN: {gstin}")
                        break
                
                results["ship_to_address"] = ship_to_lines
                if gstin:
                    results["gstin"] = gstin
            else:
                print("All ship to address patterns failed. Trying manual approach...")
                
                # Manual approach: Look for specific position after "Ship To" and before "Bill To"
                ship_to_pos = text.find("Ship To")
                bill_to_pos = text.find("Bill To")
                
                if ship_to_pos >= 0 and bill_to_pos > ship_to_pos:
                    ship_to_text = text[ship_to_pos+8:bill_to_pos].strip()
                    print(f"Manual extraction ship_to_text: {ship_to_text}")
                    ship_to_lines = [line.strip() for line in ship_to_text.split('\n') if line.strip()]
                    
                    # Filter out any lines that seem unrelated to address
                    ship_to_lines = [line for line in ship_to_lines if not line.startswith("Order") and not line.startswith("Supplier")]
                    
                    results["ship_to_address"] = ship_to_lines
                    print(f"Final manual ship_to_lines: {ship_to_lines}")
                    
                    # Try to find GSTIN in this section
                    gstin_match = re.search(r'GSTIN:\s*([A-Z0-9]+)', ship_to_text)
                    if gstin_match:
                        results["gstin"] = gstin_match.group(1)
                else:
                    results["ship_to_address"] = "Not found"
                    print("Manual extraction also failed")
            
            # Extract PO items from tables
            po_items = []
            for table in all_tables:
                print(table)
                if len(table) > 1 and any("HSN" in str(cell) for cell in table[0]):
                    print("\nFound potential items table:")
                    headers = [cell for cell in table[0]]
                    
                    # Clean headers by removing newlines and extra spaces
                    cleaned_headers = []
                    for header in headers:
                        if header:
                            cleaned_header = str(header).replace('\n', ' ').strip()
                            cleaned_headers.append(cleaned_header)
                        else:
                            cleaned_headers.append('')
                    
                    print(f"Original headers: {headers}")
                    print(f"Cleaned headers: {cleaned_headers}")
                    
                    for row in table[1:]:
                        if any("Total" in str(cell) for cell in row) or not row[0]:
                            continue
                        
                        # Create item dictionary with cleaned headers
                        item = {}
                        for i, cell in enumerate(row):
                            if i < len(cleaned_headers):
                                item[cleaned_headers[i]] = str(cell) if cell else ''
                        
                        po_items.append(item)
                        print(f"Item extracted: {item}")
            
            results["po_items"] = po_items
            
            # Extract size tables in a format that works with our template
            size_tables = []
            for table in all_tables:
                if any("COL/SIZ" in str(cell) for cell in table[0]):
                    print("\nFound potential size table:")
                    headers = []
                    if " " in str(table[0][1]):
                        headers = str(table[0][1]).strip().split()
                    
                    rows = []
                    for row in table[1:]:
                        if not row[0]:
                            continue
                        rows.append(row)
                    
                    size_tables.append({
                        "headers": headers,
                        "rows": rows
                    })
            
            results["size_tables"] = size_tables
            
            print("\n--- FINAL RESULTS ---")
            print(f"ship_to_address: {results.get('ship_to_address', 'Not found')}")
            print(f"gstin: {results.get('gstin', 'Not found')}")
            print(f"po_items: {results.get('po_items', [])}")
            
    except Exception as e:
        print(f"Error during extraction: {str(e)}")
        import traceback
        traceback.print_exc()
    
    return results

@app.post("/process")
async def process_pdf(request: dict = Body(...)):
    company = request.get("company", "")
    pdf_base64 = request.get("pdf_base64", "")

    if not pdf_base64 or not company:
        return JSONResponse(
            content={"success": False, "message": "Missing PDF data or company name"},
            status_code=400
        )
    
    # Save base64 PDF to temporary file
    try:
        with tempfile.NamedTemporaryFile(delete=False, suffix=".pdf") as temp_pdf:
            temp_pdf.write(base64.b64decode(pdf_base64))
            temp_path = temp_pdf.name
        
        try:
            if "JackJones" in company:
                result = extract_jackjones_o(temp_path)
            elif "Skechers" in company:
                result = extract_skechers(temp_path)
            elif "Puma" in company:
                result = extract_puma(temp_path)
            elif "Benetton" in company:
                result = extract_benetton(temp_path)    
            else:
                result = None

            if result is None:
                return JSONResponse(
                    content={"success": False, "message": "Failed to extract table from PDF"},
                    status_code=400
                )

            return JSONResponse(
                content={
                    "success": True,
                    "data": result,
                    # "html_table": result,
                    "company": company
                }
            )

        except Exception as e:
            return JSONResponse(
                content={"success": False, "message": f"Error processing PDF: {str(e)}"},
                status_code=500
            )
        
        finally:
            os.unlink(temp_path)
            
    except Exception as e:
        return JSONResponse(
            content={"success": False, "message": f"Error decoding PDF: {str(e)}"},
            status_code=500
        )

if __name__ == "__main__":
    import uvicorn
    uvicorn.run(app, host="0.0.0.0", port=8000)