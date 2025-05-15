from fastapi import FastAPI, Request, Body
from fastapi.templating import Jinja2Templates
from fastapi.responses import JSONResponse
from fastapi.middleware.cors import CORSMiddleware
import pdfplumber
import pandas as pd
import re
import os
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

def extract_jackjones(pdf_path):
    headers = [
        "Item", "Article Variant", "ID / Colour", "Size", "Quantity",
        "IGST", "IGST Rate (%)", "MRP", "EAN Code", "HSN"
    ]
    data_rows = []
    
    with pdfplumber.open(pdf_path) as pdf:
        page = pdf.pages[2]  
        text = page.extract_text()
        lines = text.split('\n')
        i = 0
        
        while i < len(lines) - 1:
            line = lines[i]
            next_line = lines[i + 1]
            match = re.match(
                r'^(\d+)\s+(\d+)\s+(\d+)\s+/\s+([\w/]+)\s+(\d+)\s+(Nos/Pcs)\s+([\d.]+)\s+([\d.]+)\s+([\d.]+)\s+(\d{11})\s+(\d+)',
                line
            )
            if match:
                (
                    item, article_variant, variant_id, size, quantity_value, nos_pcs,
                    igst, igst_rate, mrp, ean_partial, hsn
                ) = match.groups()
                quantity = f"{quantity_value} {nos_pcs}"
                color_line_parts = next_line.strip().rsplit(' ', 1)
                
                if len(color_line_parts) == 2:
                    color, ean_suffix = color_line_parts
                    color_parts = color.strip().split()
                    if len(color_parts) > 1 and re.match(r'^[A-Za-z0-9]{1,3}$', color_parts[-1]):
                        trailing_size = color_parts[-1]
                        color = ' '.join(color_parts[:-1])
                        size = f"{size} {trailing_size}"
                    full_ean = ean_partial + ean_suffix
                    id_colour = f"{variant_id}/{color.strip()}"
                    row = [
                        item, article_variant, id_colour, size, quantity,
                        igst, igst_rate, mrp, full_ean, hsn
                    ]
                    data_rows.append(row)
                    i += 2
                    continue
            i += 1
    
    if data_rows:
        df = pd.DataFrame(data_rows, columns=headers)
        html_table = df.to_html(index=False, border=1, classes="table table-striped table-bordered")
        responsive_table = f'<div class="table-responsive">{html_table}</div>'
        return {
            "data": df.to_dict(orient="records"),
            "html_table": responsive_table
        }
    return None

def extract_skechers(pdf_path):
    data_rows = []
    headers = None
    table_started = False
    
    with pdfplumber.open(pdf_path) as pdf:
        for page in pdf.pages:
            tables = page.extract_tables()
            for table in tables:
                for row in table:
                    if all(cell is None or str(cell).strip() == "" for cell in row):
                        continue
                    if not headers and any("Sr" in (cell or "") for cell in row):
                        headers = row
                        table_started = True
                        continue
                    if table_started and any(
                        key in (row[0] or "").lower()
                        for key in ["port of", "remarks", "shipment", "total amount payable"]
                    ):
                        table_started = False
                        break
                    if table_started:
                        data_rows.append(row)
    
    if headers is None:
        return None
    
    if headers[0] is None:
        headers = headers[1:]
        data_rows = [row[1:] for row in data_rows]
    if headers[-1] is None:
        headers = headers[:-1]
        data_rows = [row[:-1] for row in data_rows]
    
    df = pd.DataFrame(data_rows, columns=headers).dropna()
    html_table = df.to_html(index=False, border=1, classes="table table-striped table-bordered")
    responsive_table = f'<div class="table-responsive">{html_table}</div>'
    return {
        "data": df.to_dict(orient="records"),
        "html_table": responsive_table
    }

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
                result = extract_jackjones(temp_path)
            elif "Skechers" in company:
                result = extract_skechers(temp_path)
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
                    "data": result["data"],
                    "html_table": result["html_table"],
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