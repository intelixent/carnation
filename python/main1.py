"""
PO Extractor - main.py
Run: python main.py <pdf_path> <type>
  1 = Celio
  2 = Jockey
  3 = Lifestyle (F3Lifestyle)
  4 = Rare Rabbit (Radhamani / Rare Rabbit)

All output saved to output.json and output.html next to this script.
All extracted data also printed to terminal.
"""

import sys, os, re, json, webbrowser
import pdfplumber


# ─────────────────────────────────────────────────────────────────────────────
# HELPERS
# ─────────────────────────────────────────────────────────────────────────────

def first_nonempty(lst):
    for v in lst:
        v = str(v).strip() if v else ""
        if v:
            return v
    return ""

def clean(v):
    return str(v).strip().replace("\n", " ") if v else ""


# ─────────────────────────────────────────────────────────────────────────────
# 1. CELIO EXTRACTOR
# ─────────────────────────────────────────────────────────────────────────────

def extract_celio(pdf_path):
    result = {
        "type": "celio", "brand": "Celio",
        "order_no": "", "mad_date": "", "printout_date": "",
        "supplier_no": "", "your_ref": "", "name": "",
        "supplier_address": "", "delivery_address": "",
        "payment_terms": "", "delivery_terms": "", "delivery_method": "",
        "currency": "", "po_items": [],
        "total_qty": "", "total_amount": "", "amount_in_words": "",
    }
    all_items = []
    with pdfplumber.open(pdf_path) as pdf:
        for page_idx, page in enumerate(pdf.pages):
            for table in page.extract_tables():
                if not table or not table[0]:
                    continue
                flat = " ".join(str(c) for c in table[0] if c)

                if "Order no" in flat and "MAD" in flat:
                    for i, row in enumerate(table):
                        vals = [clean(c) for c in row]
                        if vals[0] and re.match(r'^\d{10}$', vals[0]):
                            result["order_no"] = vals[0]
                            result["mad_date"] = vals[1] if len(vals) > 1 else ""
                            result["printout_date"] = vals[2] if len(vals) > 2 else ""
                        if "Supplier" in vals[0] and i + 1 < len(table):
                            nxt = [clean(c) for c in table[i + 1]]
                            result["supplier_no"] = nxt[0]
                            result["your_ref"] = nxt[1] if len(nxt) > 1 else ""
                            result["name"] = nxt[2] if len(nxt) > 2 else ""

                if "Supplier Address" in flat:
                    for row in table:
                        cell = clean(row[0]) if row else ""
                        if not cell or "Supplier Address" in cell:
                            continue
                        if any(k in cell for k in ["Delivery terms", "Currency", "Payment", "Free On Board"]):
                            break
                        if re.search(r'\d|Ltd|Pvt|Road|Creations', cell):
                            result["supplier_address"] = cell
                        if len(row) >= 2:
                            k, v = clean(row[0]), clean(row[1])
                            if "Delivery terms" in k:
                                result["delivery_terms"] = v
                            if "Delivery method" in k:
                                result["delivery_method"] = v
                            if "Currency" in k:
                                result["currency"] = v
                            if "Payment terms" in k:
                                result["payment_terms"] = v

                if "Delivery Address" in flat:
                    for row in table:
                        cell = clean(row[0]) if row else ""
                        if not cell or "Delivery Address" in cell or "Invoice address" in cell:
                            continue
                        if any(k in cell for k in ["Payment", "Freight"]):
                            break
                        if re.search(r'\d|RETAIL|WAREHOUSE|CELIO', cell, re.I):
                            result["delivery_address"] = cell

                if "S. No." in flat or "S.No" in flat:
                    for row in table[1:]:
                        vals = [clean(c) for c in row]
                        if not vals[0] or not vals[0].isdigit():
                            if vals[0] == "TOTAL":
                                result["total_qty"] = first_nonempty(vals[1:8])
                                result["total_amount"] = vals[-1]
                            if "Amount in Word" in vals[0]:
                                result["amount_in_words"] = re.sub(r'^Amount in Word\w*\s*', '', vals[0]).strip()
                            continue
                        item = _celio_row_p1(vals)
                        if item:
                            all_items.append(item)

                elif page_idx > 0 and table[0][0] and clean(table[0][0]).isdigit():
                    for row in table:
                        vals = [clean(c) for c in row]
                        if not vals[0]:
                            continue
                        if not vals[0].isdigit():
                            if vals[0] == "TOTAL":
                                result["total_qty"] = first_nonempty(vals[2:8])
                                result["total_amount"] = vals[-1]
                            if "Amount in Word" in vals[0]:
                                result["amount_in_words"] = re.sub(r'^Amount in Word\w*\s*', '', vals[0]).strip()
                            continue
                        item = _celio_row_p2(vals)
                        if item:
                            all_items.append(item)

    result["po_items"] = all_items
    return result

def _celio_row_p1(v):
    try:
        return {
            "sno": v[0],
            "product_code": v[1],
            "description": v[2],
            "colour": v[2],
            "size": v[3],
            "quantity": v[6].replace(" ", ""),
            "uom": v[7],
            "rate": v[8],
            "igst_rate": v[13],
            "igst_amount": v[14],
            "amount": v[15] if len(v) > 15 else ""
        }
    except:
        return None

def _celio_row_p2(v):
    try:
        return {
            "sno": v[0],
            "product_code": v[1],
            "description": v[2],
            "colour": v[2],
            "size": v[3],
            "quantity": v[7].replace(" ", "") if len(v) > 7 else "",
            "uom": v[8] if len(v) > 8 else "",
            "rate": v[10] if len(v) > 10 else "",
            "igst_rate": v[19] if len(v) > 19 else "",
            "igst_amount": v[22] if len(v) > 22 else "",
            "amount": v[23] if len(v) > 23 else ""
        }
    except:
        return None


# ─────────────────────────────────────────────────────────────────────────────
# 2. JOCKEY EXTRACTOR
# ─────────────────────────────────────────────────────────────────────────────

def extract_jockey(pdf_path):
    result = {
        "type": "jockey", "brand": "Jockey (Page Industries)",
        "order_no": "", "po_date": "", "plant": "",
        "vendor_name": "", "vendor_address": "", "vendor_gstin": "",
        "buyer_gstin": "", "delivery_address": "",
        "payment_terms": "", "inco_terms": "", "material_name": "",
        "po_items": [], "subtotal": "", "igst": "",
        "total_amount": "", "amount_in_words": "",
    }
    with pdfplumber.open(pdf_path) as pdf:
        page = pdf.pages[0]
        for table in page.extract_tables():
            for row in table:
                if not row:
                    continue
                c0 = clean(row[0])
                c5 = clean(row[5]) if len(row) > 5 else ""
                if "VENDOR: Company" in c0:
                    m = re.search(r'VENDOR: Company\s+(.+?)(?:\n|SF )', c0)
                    if m:
                        result["vendor_name"] = m.group(1).strip()
                    am = re.search(r'(SF .+?)(?:\nGSTIN|$)', c0, re.DOTALL)
                    if am:
                        result["vendor_address"] = am.group(1).strip().replace("\n", ", ")
                    gm = re.search(r'GSTIN\.:\s*([A-Z0-9]+)', c0)
                    if gm:
                        result["vendor_gstin"] = gm.group(1)
                    if c5:
                        pm = re.search(r'Purchase Order No:\s*(\d+)', c5)
                        if pm:
                            result["order_no"] = pm.group(1)
                        dm = re.search(r'PO Date:\s*([\d.]+)', c5)
                        if dm:
                            result["po_date"] = dm.group(1)
                        plm = re.search(r'Plant\s*:\s*(\d+)', c5)
                        if plm:
                            result["plant"] = plm.group(1)
                        bm = re.search(r'GSTIN\s*:\s*([A-Z0-9]+)', c5)
                        if bm:
                            result["buyer_gstin"] = bm.group(1)
                if re.match(r'^1$', c0) and row[1]:
                    result["material_name"] = clean(row[1]).replace("\n", " — ")
                if "PAYMENT TERMS" in c0:
                    pt = re.search(r'PAYMENT TERMS\s*:\s*(.+?)(?:\n|INCO|$)', c0)
                    if pt:
                        result["payment_terms"] = pt.group(1).strip()
                    it = re.search(r'INCO TERMS\s*:\s*(.+)', c0)
                    if it:
                        result["inco_terms"] = it.group(1).strip()
                if "Delivery Address:" in c0:
                    da = re.search(r'Delivery Address:\s*(.+?)(?:\n|PREPARED|$)', c0, re.DOTALL)
                    if da:
                        result["delivery_address"] = da.group(1).strip().replace("\n", ", ")
                if "Sub-Total Amount:" in c0 and len(row) > 8 and row[8]:
                    amts = clean(row[8]).split("\n")
                    if amts:
                        result["subtotal"] = amts[0]
                    if len(amts) > 5:
                        result["igst"] = amts[5]
                    for a in amts:
                        if re.match(r'^[\d,]+\.\d{2}$', a.strip()):
                            result["total_amount"] = a.strip()
                if "Amount in Words:" in c0:
                    aw = re.search(r'Amount in Words:\s*(.+)', c0)
                    if aw:
                        result["amount_in_words"] = aw.group(1).strip()

        items = []
        for table in page.extract_tables():
            for row in table:
                if not row:
                    continue
                vals = [clean(c) for c in row]
                if re.match(r'^\d+\s*\.\s*\d+$', vals[0]):
                    item = _jockey_item(vals)
                    if item:
                        items.append(item)
        result["po_items"] = items
    return result

def _jockey_item(v):
    try:
        desc = v[1]
        sm = re.search(r',\s*([A-Z]+)\s*$', desc)
        size = sm.group(1) if sm else ""
        return {
            "sl_no": v[0].replace(" ", ""),
            "description": re.sub(r',\s*[A-Z]+\s*$', '', desc).strip(),
            "size": size,
            "hsn": v[2],
            "delivery_date": v[4],
            "quantity": v[5],
            "uom": v[6],
            "basic_price": v[7],
            "amount": v[8],
            "igst": v[9],
            "cgst": v[10],
            "sgst": v[11] if len(v) > 11 else ""
        }
    except:
        return None


# ─────────────────────────────────────────────────────────────────────────────
# 3. LIFESTYLE (F3Lifestyle) EXTRACTOR  ← FULLY REWRITTEN
# ─────────────────────────────────────────────────────────────────────────────
#
# Key findings from PDF analysis:
#   • Header lives in page[0] table[0] rows[1] and rows[2]
#       – row[1][0]  → Purchaser block (name, address, GSTIN)
#       – row[1][7]  → PO numbers block  (PO No, PO Date, Expiry, Delivery)
#       – row[2][0]  → Sender block      (vendor name, address, GSTIN)
#   • Item rows: page 1 table has 13 cols (extra None at col[7])
#                pages 2-4 tables have 12 cols (no phantom col)
#       Col map (page 1 / pages 2+):
#         [0]  sno
#         [1]  product_code
#         [2]  description  (multiline: "Regular Fit\n<Colour>\nKnitted Shirt\n<Size-num>")
#         [3]  qty
#         [4]  image (empty)
#         [5]  rate
#         [6]  size_num (numeric part of size)
#         [7]  None (page1 only phantom col) / discount (pages 2+)
#         [8]  discount (page1)             / taxable_value (pages 2+)
#         [9]  taxable_value (page1)        / igst (pages 2+)
#         [10] igst (page1)                 / cess (pages 2+)
#         [11] cess (page1)                 / amount (pages 2+)
#         [12] amount (page1 only)
#   • Totals row in page 4 table: ['', 'Total', '', '1200', '', '', '', None/'', '', '960000.00', '48000.00', '', '1008000.00']
#   • Amount-in-words row: col[0] contains "INR ..."
#   • Payment terms: col[0] starts with "Payment Terms:"

def _parse_lifestyle_description(raw_desc):
    """
    Parse the multiline description cell from the PDF table.
    Format: "Regular Fit\n<Colour Line(s)>\nKnitted Shirt\n<Size-Label>"
    Returns (full_description, colour, size_label)
    """
    lines = [l.strip() for l in raw_desc.strip().split("\n") if l.strip()]
    # Last line is always the size label (e.g. "XS-36", "M-40")
    size_label = lines[-1] if lines else ""
    # Lines between first ("Regular Fit") and last line before "Knitted Shirt" = colour
    colour_lines = []
    shirt_idx = None
    for i, l in enumerate(lines):
        if "Knitted Shirt" in l:
            shirt_idx = i
            break
    if shirt_idx is not None and shirt_idx > 1:
        colour_lines = lines[1:shirt_idx]
    colour = " ".join(colour_lines)
    full_desc = " ".join(lines[:-1])   # everything except the trailing size line
    return full_desc, colour, size_label


def _lifestyle_item_from_row(row, is_page1=None):
    """Extract one PO item from a table row.
    Column layout detected by row length:
      13 cols → col[7] is phantom None  (pages 1 & 4)
      12 cols → no phantom col          (pages 2 & 3)
    """
    vals = [v.strip() if v else "" for v in row]
    sno = vals[0]
    if not sno or not sno.isdigit():
        return None

    product_code = vals[1]
    raw_desc     = row[2] or ""          # keep original \n for parsing
    qty          = vals[3]
    rate         = vals[5]
    size_num     = vals[6]               # numeric size column

    if len(row) == 13:
        # 13-col layout: col[7] is phantom None
        discount      = vals[8]
        taxable_value = vals[9]
        igst_raw      = vals[10]
        cess_raw      = vals[11]
        amount        = vals[12]
    else:
        # 12-col layout
        discount      = vals[7]
        taxable_value = vals[8]
        igst_raw      = vals[9]
        cess_raw      = vals[10]
        amount        = vals[11]

    # Strip percentage lines from IGST/CESS  e.g. "320.00\n(5.000%)"
    igst_amount = igst_raw.split("\n")[0].strip() if igst_raw else ""
    igst_pct    = ""
    m = re.search(r'\(([\d.]+)%\)', igst_raw)
    if m:
        igst_pct = m.group(1) + "%"

    cess_amount = cess_raw.split("\n")[0].strip() if cess_raw else ""

    full_desc, colour, size_label = _parse_lifestyle_description(raw_desc)

    return {
        "sno":           sno,
        "product_code":  product_code,
        "description":   full_desc,
        "colour":        colour,
        "size":          size_label,
        "quantity":      qty,
        "rate":          rate,
        "discount":      discount,
        "taxable_value": taxable_value,
        "igst_pct":      igst_pct,
        "igst_amount":   igst_amount,
        "cess_amount":   cess_amount,
        "amount":        amount,
    }


def extract_lifestyle(pdf_path):
    result = {
        "type":               "lifestyle",
        "brand":              "F3Lifestyle",
        "order_no":           "",
        "order_date":         "",
        "expiry_date":        "",
        "delivery_date":      "",
        "purchaser_name":     "",
        "purchaser_address":  "",
        "purchaser_gstin":    "",
        "vendor_name":        "",
        "vendor_address":     "",
        "vendor_gstin":       "",
        "payment_terms":      "",
        "po_items":           [],
        "total_qty":          "",
        "total_taxable":      "",
        "total_igst":         "",
        "total_amount":       "",
        "amount_in_words":    "",
    }

    all_items = []

    with pdfplumber.open(pdf_path) as pdf:
        # ── HEADER: page 0, table 0 ──────────────────────────────────────────
        page0 = pdf.pages[0]
        tables0 = page0.extract_tables()

        if tables0:
            header_table = tables0[0]

            # Row index 1 → Purchaser (col 0) + PO details (col 7)
            if len(header_table) > 1:
                purchaser_cell = header_table[1][0] or ""
                po_details_cell = header_table[1][7] if len(header_table[1]) > 7 else ""

                # ── Purchaser ──
                lines = [l.strip() for l in purchaser_cell.split("\n") if l.strip()]
                # lines[0] = "Purchaser", lines[1] = company name, rest = address + GSTIN
                name_idx = 1
                for i, l in enumerate(lines):
                    if l == "Purchaser":
                        name_idx = i + 1
                        break
                if name_idx < len(lines):
                    result["purchaser_name"] = lines[name_idx]
                addr_lines = []
                for l in lines[name_idx + 1:]:
                    if l.startswith("GSTIN"):
                        gm = re.search(r'GSTIN\s*:([\w]+)', l)
                        if gm:
                            result["purchaser_gstin"] = gm.group(1)
                    elif l.startswith("Ph No"):
                        pass   # skip phone
                    else:
                        addr_lines.append(l)
                result["purchaser_address"] = ", ".join(addr_lines)

                # ── PO Details ──
                if po_details_cell:
                    om = re.search(r'Purchase Order No\.\s*\n?([\w]+)', po_details_cell)
                    if om:
                        result["order_no"] = om.group(1).strip()
                    dtm = re.search(r'Purchase Order Date\s*\n?(.+?)(?:\n|Expiry|$)', po_details_cell)
                    if dtm:
                        result["order_date"] = dtm.group(1).strip()
                    exm = re.search(r'Expiry Date:\s*\n?(.+?)(?:\n|Delivery|$)', po_details_cell)
                    if exm:
                        result["expiry_date"] = exm.group(1).strip()
                    dlm = re.search(r'Delivery Date:\s*\n?(.+)', po_details_cell)
                    if dlm:
                        result["delivery_date"] = dlm.group(1).strip()

            # Row index 2 → Sender / Vendor (col 0)
            if len(header_table) > 2:
                sender_cell = header_table[2][0] or ""
                lines = [l.strip() for l in sender_cell.split("\n") if l.strip()]
                # lines[0] = "Sender:", lines[1] = vendor name, etc.
                for i, l in enumerate(lines):
                    if l.startswith("Sender:"):
                        remainder = l[len("Sender:"):].strip()
                        if remainder:
                            result["vendor_name"] = remainder
                        elif i + 1 < len(lines):
                            result["vendor_name"] = lines[i + 1]
                        break

                addr_lines = []
                past_name = False
                for l in lines:
                    if l.startswith("Sender:"):
                        past_name = True
                        continue
                    if not past_name:
                        continue
                    if l == result["vendor_name"]:
                        continue
                    if l.startswith("GSTIN"):
                        gm = re.search(r'GSTIN\s*:([\w]+)', l)
                        if gm:
                            result["vendor_gstin"] = gm.group(1)
                    elif l.startswith("Ph No"):
                        pass
                    else:
                        addr_lines.append(l)
                result["vendor_address"] = ", ".join(addr_lines)

        # ── ITEMS: all pages, all tables ─────────────────────────────────────
        for page_idx, page in enumerate(pdf.pages):
            is_page1 = (page_idx == 0)
            for table in page.extract_tables():
                if not table:
                    continue
                for row in table:
                    if not row or not row[0]:
                        continue
                    vals = [v.strip() if v else "" for v in row]

                    # Skip header rows
                    if vals[0] in ("SI No.", ""):
                        continue

                    # Item row: col[0] is a digit
                    if vals[0].isdigit():
                        item = _lifestyle_item_from_row(row)
                        if item:
                            all_items.append(item)
                        continue

                    # Totals row: col[1] == "Total"
                    if vals[1] == "Total":
                        # Detect by row length (13-col vs 12-col)
                        if len(row) == 13:
                            result["total_qty"]     = vals[3]
                            result["total_taxable"] = vals[9]
                            result["total_igst"]    = vals[10]
                            result["total_amount"]  = vals[12]
                        else:
                            result["total_qty"]     = vals[3]
                            result["total_taxable"] = vals[8]
                            result["total_igst"]    = vals[9]
                            result["total_amount"]  = vals[11]
                        continue

                    # Amount in words row
                    c0 = vals[0]
                    if "Amount Chargeable" in c0 or "INR " in c0:
                        aw_m = re.search(r'INR\s+(.+)', c0)
                        if aw_m:
                            result["amount_in_words"] = aw_m.group(1).strip()
                        continue

                    # Payment terms row
                    if "Payment Terms:" in c0:
                        pt_m = re.search(r'Payment Terms:\s*(.+?)(?:\n|Vendor|$)', c0)
                        if pt_m:
                            result["payment_terms"] = pt_m.group(1).strip()
                        continue

    result["po_items"] = all_items
    return result


# ─────────────────────────────────────────────────────────────────────────────
# 4. RARE RABBIT (Radhamani Textiles) EXTRACTOR
# ─────────────────────────────────────────────────────────────────────────────

def extract_rare_rabbit(pdf_path):
    result = {
        "type": "rare_rabbit", "brand": "Rare Rabbit",
        "order_no": "", "order_date": "", "channel": "",
        "purchaser_name": "RADHAMANI TEXTILES PVT LTD",
        "purchaser_address": "", "purchaser_gstin": "",
        "vendor_name": "", "vendor_id": "", "vendor_address": "", "vendor_gstin": "",
        "delivery_date": "", "payment_terms": "", "currency": "",
        "po_items": [],
        "total_qty": "", "total_basic": "", "igst_amount": "",
        "net_amount": "", "amount_in_words": "",
    }
    with pdfplumber.open(pdf_path) as pdf:
        page = pdf.pages[0]
        text = page.extract_text() or ""

        om = re.search(r'Order No\.\s*:\s*(.+)', text)
        if om:
            result["order_no"] = om.group(1).strip()
        dm = re.search(r'Date\s*:\s*([\d\-]+)', text)
        if dm:
            result["order_date"] = dm.group(1).strip()
        cm = re.search(r'Channel\s*:\s*(.+)', text)
        if cm:
            result["channel"] = cm.group(1).strip()
        gm = re.search(r'GSTIN\s*:([A-Z0-9\[\]]+)', text)
        if gm:
            result["purchaser_gstin"] = gm.group(1).strip()

        vnm = re.search(r'Vendor Name\s*:(.+?)(?:Vendor ID|$)', text)
        if vnm:
            result["vendor_name"] = vnm.group(1).strip()
        vid = re.search(r'Vendor ID\s*:\s*(\S+)', text)
        if vid:
            result["vendor_id"] = vid.group(1).strip()

        va_lines = []
        in_addr = False
        for line in text.split("\n"):
            if "Vendor Address" in line:
                in_addr = True
                part = line.split("Vendor Address")[1].replace(":", "").strip()
                if part:
                    va_lines.append(part)
                continue
            if in_addr:
                if any(k in line for k in ["Delivery Date", "Doc No", "Contact", "Email", "GST State", "Payment"]):
                    break
                if line.strip():
                    va_lines.append(line.strip())
        result["vendor_address"] = ", ".join(va_lines)

        vg = re.search(r':(33[A-Z0-9]+)', text)
        if vg:
            result["vendor_gstin"] = vg.group(1)
        ddm = re.search(r'Delivery Date\s*:\s*([\d\-]+)', text)
        if ddm:
            result["delivery_date"] = ddm.group(1).strip()
        ptm = re.search(r'Payment Terms\s*\(Days\)\s*:\s*(\d+)', text)
        if ptm:
            result["payment_terms"] = ptm.group(1) + " days"
        curr = re.search(r'Currency\s*:\s*(\w+)', text)
        if curr:
            result["currency"] = curr.group(1)

        items = []
        for table in page.extract_tables():
            for row in table:
                if not row or not row[0]:
                    continue
                code = clean(row[0])
                if not re.match(r'^RR\d+', code):
                    continue
                desc_full = clean(row[1]) if len(row) > 1 else ""
                parts = desc_full.split("\n")
                desc = parts[0].strip() if parts else desc_full
                size_season = parts[1].strip() if len(parts) > 1 else ""
                sm = re.match(r'^([A-Z]+)\s+', size_season)
                size = sm.group(1) if sm else ""
                hsn = clean(row[6]) if len(row) > 6 else ""
                rate = clean(row[9]) if len(row) > 9 else ""
                qty = clean(row[11]) if len(row) > 11 else ""
                uom = clean(row[12]) if len(row) > 12 else ""
                basic = clean(row[13]) if len(row) > 13 else ""
                items.append({
                    "item_code": code,
                    "description": desc,
                    "size": size,
                    "season": size_season,
                    "hsn": hsn,
                    "rate": rate,
                    "quantity": qty,
                    "uom": uom,
                    "basic_amount": basic
                })
        result["po_items"] = items

        tot = re.search(r'Total\s+([\d,]+\.000)\s+([\d,]+\.00)', text)
        if tot:
            result["total_qty"] = tot.group(1)
            result["total_basic"] = tot.group(2)
        igst = re.search(r'Integrated GST.*?\]\s*([\d,]+\.00)', text)
        if igst:
            result["igst_amount"] = igst.group(1)
        net = re.search(r'Net Amount\s+([\d,]+\.00)', text)
        if net:
            result["net_amount"] = net.group(1)
        aw = re.search(r'Amount in Words\s*:\s*(.+)', text)
        if aw:
            result["amount_in_words"] = aw.group(1).strip()

    return result


# ─────────────────────────────────────────────────────────────────────────────
# PRINT TO TERMINAL
# ─────────────────────────────────────────────────────────────────────────────

def print_extracted(data):
    print("\n" + "=" * 70)
    print(f"  BRAND: {data['brand']}  |  TYPE: {data['type'].upper()}")
    print("=" * 70)

    skip = {"type", "brand", "po_items"}
    for k, v in data.items():
        if k in skip:
            continue
        if v:
            print(f"  {k.replace('_', ' ').upper():<25} : {v}")

    print(f"\n  PO ITEMS ({len(data['po_items'])} rows):")
    print("  " + "-" * 65)
    for item in data["po_items"]:
        vals = "  |  ".join(f"{kk}: {vv}" for kk, vv in item.items() if vv)
        print(f"  {vals}")
    print("=" * 70 + "\n")


# ─────────────────────────────────────────────────────────────────────────────
# HTML RENDER
# ─────────────────────────────────────────────────────────────────────────────

def render_html(data):
    brand = data["brand"]

    skip = {"type", "brand", "po_items"}
    info_rows = ""
    for k, v in data.items():
        if k in skip or not v:
            continue
        info_rows += f"<tr><td><b>{k.replace('_', ' ').title()}</b></td><td>{v}</td></tr>\n"

    if data["po_items"]:
        cols = list(data["po_items"][0].keys())
        th = "".join(f"<th>{c.replace('_', ' ').title()}</th>" for c in cols)
        item_rows = ""
        for item in data["po_items"]:
            tds = "".join(f"<td>{item.get(c, '')}</td>" for c in cols)
            item_rows += f"<tr>{tds}</tr>\n"
        items_table = (
            f"<table border='1' cellpadding='4' cellspacing='0'>"
            f"<thead><tr>{th}</tr></thead>"
            f"<tbody>{item_rows}</tbody></table>"
        )
    else:
        items_table = "<p>No items found.</p>"

    return f"""<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"><title>PO - {brand}</title>
<style>
  body {{ font-family: Arial, sans-serif; margin: 20px; }}
  table {{ border-collapse: collapse; margin-bottom: 20px; }}
  th {{ background: #4472C4; color: white; padding: 6px 10px; }}
  td {{ padding: 5px 10px; }}
  tr:nth-child(even) {{ background: #f2f2f2; }}
  h2 {{ color: #4472C4; }}
</style>
</head>
<body>
<h2>Purchase Order — {brand}</h2>
<h3>PO Details</h3>
<table border="1" cellpadding="4" cellspacing="0">
<tbody>{info_rows}</tbody>
</table>
<h3>PO Items ({len(data['po_items'])} rows)</h3>
{items_table}
</body>
</html>"""


# ─────────────────────────────────────────────────────────────────────────────
# ENTRY POINT
# ─────────────────────────────────────────────────────────────────────────────

EXTRACTORS = {
    "1": ("Celio", extract_celio),
    "2": ("Jockey", extract_jockey),
    "3": ("Lifestyle", extract_lifestyle),
    "4": ("Rare Rabbit", extract_rare_rabbit),
}

def main():
    if len(sys.argv) == 3:
        pdf_path = sys.argv[1]
        po_type  = sys.argv[2]
    else:
        # Hardcoded fallback for direct run
        pdf_path = r"C:\Users\Ram Rishi\Downloads\check\Celio.pdf"
        po_type  = "1"
        
        # pdf_path = r"C:\Users\Ram Rishi\Downloads\check\jockey.pdf"
        # po_type  = "2"
        
        # pdf_path = r"C:\Users\Ram Rishi\Downloads\check\Lifestyle.pdf"
        # po_type  = "3"
        
        # pdf_path = r"C:\Users\Ram Rishi\Downloads\check\RareRabbit.pdf"
        # po_type  = "4"

    if not os.path.exists(pdf_path):
        print(f"Error: file not found → {pdf_path}")
        sys.exit(1)

    brand_name, extractor_fn = EXTRACTORS[po_type]
    print(f"\nExtracting [{brand_name}] from: {pdf_path}")

    data = extractor_fn(pdf_path)
    print_extracted(data)

    script_dir = os.path.dirname(os.path.abspath(__file__))
    json_path  = os.path.join(script_dir, "output.json")
    html_path  = os.path.join(script_dir, "output.html")

    with open(json_path, "w", encoding="utf-8") as f:
        json.dump(data, f, indent=2, ensure_ascii=False)
    print(f"JSON saved → {json_path}")

    with open(html_path, "w", encoding="utf-8") as f:
        f.write(render_html(data))
    print(f"HTML saved → {html_path}")

    webbrowser.open(f"file://{html_path}")
    print("Done ✓")

if __name__ == "__main__":
    main()