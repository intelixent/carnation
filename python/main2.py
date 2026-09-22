"""
PO Extractor - main.py
Run: python main.py <pdf_path> <type>
  1 = Celio
  2 = Jockey
  3 = Lifestyle (F3Lifestyle)
  4 = Rare Rabbit (Radhamani / Rare Rabbit)
  5 = BALMOHK / BESTSELLER Uruguay
  6 = NEXT Sourcing Limited
  7 = D-Mart (Avenue Supermarts)

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
# 5. BALMOHK / BESTSELLER URUGUAY EXTRACTOR
# ─────────────────────────────────────────────────────────────────────────────
#
# PDF has NO pdfplumber-detectable tables on pages 1–2; all text-based.
# Page 3 has two packing-info tables (assortment breakdown per colour).
# Page 4 has one summary table + EAN table, both text-based.
#
# Key fields extracted:
#   Page 1 (text): order_no, print_date, collection, order_stat_type,
#                  creation_date, changed_date, destination,
#                  vendor_name, vendor_address,
#                  style_no, ean, style_name, gender, category,
#                  quantity, price_per_item, total_amount, currency,
#                  composition, hs_code,
#                  labels (barcode, care, hang tag, main, size)
#   Page 2 (text): payment_terms, cargo_closing_dates[], cancel_dates[],
#                  port_of_loading, transportation, terms_of_delivery
#   Page 3 (text+table): distribution[], packing_info (per-assortment size breakdown)
#   Page 4 (text+table): production_summary (colour × size totals),
#                        ean_table (colour, size, colour_code, ean_no),
#                        buyer_contact, buyer_company_address

def _collapse_spaces(s):
    """Collapse multiple spaces to one and strip."""
    return re.sub(r'  +', ' ', s).strip() if s else ""

def extract_balmohk(pdf_path):
    result = {
        "type":              "balmohk",
        "brand":             "BALMOHK / BESTSELLER",
        # Page 1
        "order_no":          "",
        "print_date":        "",
        "collection":        "",
        "order_stat_type":   "",
        "creation_date":     "",
        "changed_date":      "",
        "destination":       "",
        "vendor_name":       "",
        "vendor_address":    "",
        "style_no":          "",
        "ean":               "",
        "style_name":        "",
        "gender":            "",
        "category":          "",
        "quantity":          "",
        "price_per_item":    "",
        "total_amount":      "",
        "currency":          "",
        "composition":       "",
        "hs_code":           "",
        "label_barcode":     "",
        "label_care":        "",
        "label_hang_tag":    "",
        "label_main":        "",
        "label_size":        "",
        # Page 2
        "payment_terms":     "",
        "port_of_loading":   "",
        "transportation":    "",
        "terms_of_delivery": "",
        "dates":             [],   # list of {no, country, ccd, cancel_date}
        # Page 3
        "distribution":      [],   # list of {no, country, assortment, polybag, boxes, qty_per_box, quantity}
        "packing_info":      [],   # list of {assortment, colour, model, xs,s,m,l,xl, total}
        # Page 4
        "production_summary":[],   # list of {colour, model, xs,s,m,l,xl, total}
        "ean_table":         [],   # list of {colour, size, colour_code, ean_no}
        # Buyer info
        "buyer_contact":     "",
        "buyer_company":     "",
        "buyer_address":     "",
        "buyer_vat":         "",
        "buyer_website":     "",
    }

    with pdfplumber.open(pdf_path) as pdf:

        # ── PAGE 1 ────────────────────────────────────────────────────────────
        text1 = pdf.pages[0].extract_text() or ""

        def field(pattern, text, flags=0):
            m = re.search(pattern, text, flags)
            return m.group(1).strip() if m else ""

        result["order_no"]        = field(r'Order no\.\s+(\S+)', text1)
        result["print_date"]      = field(r'Print date\s+(.+)', text1)
        result["collection"]      = field(r'Collection\s+(.+)', text1)
        result["order_stat_type"] = field(r'Order stat\. type\s+(.+)', text1)
        result["creation_date"]   = field(r'Creation date\s+(.+)', text1)
        result["changed_date"]    = field(r'Changed date\s+(.+)', text1)
        result["destination"]     = field(r'Destination\s+(.+)', text1)
        result["hs_code"]         = field(r'HS Code:\s*(\S+)', text1)
        result["composition"]     = field(r'Composition\s*\n(.+)', text1)
        result["label_barcode"]   = field(r'Barcode Label:\s*(.+)', text1)
        result["label_care"]      = field(r'Care Label:\s*(.+)', text1)
        result["label_hang_tag"]  = field(r'Hang Tag:\s*(.+)', text1)
        result["label_main"]      = field(r'Main Label:\s*(.+)', text1)
        result["label_size"]      = field(r'Size Label:\s*(.+)', text1)
        result["style_no"]        = field(r'No:\s*(\S+)', text1)
        result["ean"]             = field(r'EAN:\s*(\S+)', text1)
        result["style_name"]      = field(r'Name:\s*(.+)', text1)
        result["gender"]          = field(r'Gender:\s*(.+)', text1)
        result["category"]        = field(r'Category:\s*(.+)', text1)
        result["quantity"]        = field(r'Quantity:\s*([\d,]+\s*PCS\.?)', text1)
        result["price_per_item"]  = field(r'Price per item:\s*([\d,\.]+ \w+)', text1)
        m_amt = re.search(r'Total amount:\s*([\d,\.]+)\s*(\w+)', text1)
        if m_amt:
            result["total_amount"] = m_amt.group(1)
            result["currency"]     = m_amt.group(2)

        # Vendor: lines before "Order no." on the left column
        lines1 = text1.split("\n")
        vendor_lines = []
        for l in lines1:
            stripped = l.strip()
            if stripped in ("PURCHASE ORDER", ""):
                continue
            # vendor block lines appear before we hit Order / Collection keywords
            if re.match(r'(Order no\.|Collection|Order stat\.|Print date)', stripped):
                break
            # Filter out right-column only lines (contain keywords)
            if re.search(r'(INDIA$|641031|Coimbatore|Narasimha|376/1)', stripped):
                vendor_lines.append(stripped)
        result["vendor_name"]    = vendor_lines[0] if vendor_lines else ""
        result["vendor_address"] = ", ".join(vendor_lines[1:]) if len(vendor_lines) > 1 else ""

        # ── PAGE 2 ────────────────────────────────────────────────────────────
        text2 = pdf.pages[1].extract_text() or ""

        result["payment_terms"]    = field(r'TERMS OF PAYMENT:\s*(.+)', text2)
        result["port_of_loading"]  = field(r'#1 LATAM SOUTH UY\s+(.+?)\s+(?:BY SEA|BY AIR)', text2)
        result["transportation"]   = field(r'#1 LATAM SOUTH UY\s+\S+(?:\s+\S+)?\s+(BY SEA|BY AIR)', text2)
        result["terms_of_delivery"]= field(r'#1 LATAM SOUTH UY\s+\S+(?:\s+\S+)?\s+(?:BY SEA|BY AIR)\s+(.+)', text2)

        for m in re.finditer(
            r'#\s*(\d+)\s+LATAM SOUTH UY\s+([\d\-]+)\s+([\d\-]+)', text2):
            result["dates"].append({
                "no": m.group(1),
                "country": "LATAM SOUTH UY",
                "cargo_closing_date": m.group(2),
                "cancel_date": m.group(3),
            })

        # ── PAGE 3 ────────────────────────────────────────────────────────────
        text3 = pdf.pages[2].extract_text() or ""

        # Distribution rows: "#1 LATAM SOUTH UY  <assortment>  YES  17  15  255"
        for m in re.finditer(
            r'#(\d+)\s+LATAM SOUTH UY\s+(.+?)\s+(YES|NO)\s+(\d+)\s+(\d+)\s+(\d+)',
            text3, re.IGNORECASE):
            result["distribution"].append({
                "no":          m.group(1),
                "country":     "LATAM SOUTH UY",
                "assortment":  m.group(2).strip(),
                "polybag":     m.group(3),
                "boxes":       m.group(4),
                "qty_per_box": m.group(5),
                "quantity":    m.group(6),
            })

        # Packing info tables — use pdfplumber table data for page 3
        for table in pdf.pages[2].extract_tables():
            if not table or len(table) < 3:
                continue
            # Find assortment name from header row[0]
            assortment_name = ""
            header_cell = table[0][0] or ""
            m_ass = re.search(r'Ass\.\s*:"(.+?)"', header_cell)
            if m_ass:
                assortment_name = m_ass.group(1)
            # Data rows (skip header rows — rows with sizes in col[1..5])
            for row in table:
                if not row or not row[0]:
                    continue
                r0 = (row[0] or "").strip()
                # Skip header/total marker rows
                if r0 in ("Total", "") or "Ass." in r0 or "No. #" in r0 or "Business" in r0:
                    continue
                # Colour data row: "Winetasting Seasonal NOOS", "2","4","4","3","2","15"
                parts = r0.rsplit(" ", 2)  # e.g. ["Winetasting", "Seasonal", "NOOS"]
                colour = parts[0] if parts else r0
                model  = " ".join(parts[1:]) if len(parts) > 1 else ""
                sizes  = [str(c).strip() if c else "" for c in row[1:]]
                if any(s.isdigit() for s in sizes):
                    result["packing_info"].append({
                        "assortment": assortment_name,
                        "colour":     colour,
                        "model":      model,
                        "xs":         sizes[0] if len(sizes) > 0 else "",
                        "s":          sizes[1] if len(sizes) > 1 else "",
                        "m":          sizes[2] if len(sizes) > 2 else "",
                        "l":          sizes[3] if len(sizes) > 3 else "",
                        "xl":         sizes[4] if len(sizes) > 4 else "",
                        "total":      sizes[5] if len(sizes) > 5 else "",
                    })

        # ── PAGE 4 ────────────────────────────────────────────────────────────
        text4 = pdf.pages[3].extract_text() or ""

        # Production summary from table (page 4 has one detected table)
        for table in pdf.pages[3].extract_tables():
            if not table:
                continue
            for row in table:
                if not row or not row[0]:
                    continue
                r0 = (row[0] or "").strip()
                if r0 in ("Total", "") or "Business" in r0 or "Length" in r0:
                    continue
                parts = r0.rsplit(" ", 2)
                colour = parts[0] if parts else r0
                model  = " ".join(parts[1:]) if len(parts) > 1 else ""
                sizes  = [str(c).strip() if c else "" for c in row[1:]]
                if any(s.isdigit() for s in sizes):
                    result["production_summary"].append({
                        "colour": colour,
                        "model":  model,
                        "xs":     sizes[0] if len(sizes) > 0 else "",
                        "s":      sizes[1] if len(sizes) > 1 else "",
                        "m":      sizes[2] if len(sizes) > 2 else "",
                        "l":      sizes[3] if len(sizes) > 3 else "",
                        "xl":     sizes[4] if len(sizes) > 4 else "",
                        "total":  sizes[5] if len(sizes) > 5 else "",
                    })

        # EAN table: detect by finding lines ending in 13-digit EAN
        # "Black XS C-N10 5715909686136" or "Winetasting XS 19-2118 TCX 5715911763672"
        for line in text4.split("\n"):
            line = line.strip()
            parts = line.split()
            if not parts:
                continue
            ean = parts[-1]
            if not (ean.isdigit() and len(ean) == 13):
                continue
            # Find size token (XS/S/M/L/XL/XXL)
            size_m = re.search(r'\b(XXL|XS|XL|S|M|L)\b', line)
            if not size_m:
                continue
            size = size_m.group(1)
            colour = line[:size_m.start()].strip()
            # colour_code = everything between size and EAN
            after_size = line[size_m.end():].strip()
            colour_code = after_size.replace(ean, "").strip()
            result["ean_table"].append({
                "colour":      colour,
                "size":        size,
                "colour_code": colour_code,
                "ean_no":      ean,
            })

        # Buyer / footer info from page 4 text
        result["buyer_contact"] = field(r'Best regards,\s*\n(.+)', text4)
        # Buyer address block — strip right-column fields (Phone/Fax/VAT/www)
        buyer_lines = []
        in_buyer = False
        for line in text4.split("\n"):
            if "BESTSELLER Latam ZF SA" in line:
                in_buyer = True
            if in_buyer:
                if line.strip() == "URUGUAY":
                    buyer_lines.append("URUGUAY")
                    break
                left = re.sub(r'\s+(Phone|Fax|VAT no\.|www)\s+\S+.*', '', line).strip()
                if left:
                    buyer_lines.append(left)
        if buyer_lines:
            result["buyer_company"] = buyer_lines[0]
            result["buyer_address"] = ", ".join(buyer_lines[1:])
        result["buyer_vat"]     = field(r'VAT no\.\s*([\d]+)', text4)
        result["buyer_website"] = field(r'(www\.\S+)', text4)

    # po_items for unified HTML rendering — use ean_table as primary item list
    result["po_items"] = result["ean_table"]
    return result


# ─────────────────────────────────────────────────────────────────────────────
# 6. NEXT SOURCING LIMITED EXTRACTOR
# ─────────────────────────────────────────────────────────────────────────────
#
# PDF has NO pdfplumber tables — all text-based (spaced/kerned characters).
# Key findings:
#   • Header fields scattered across first few lines (to/address, PO no, season…)
#   • Item table starts after "ITEM/ OPT NO. DESCRIPTION QUANTITY UNIT PRICE AMOUNT"
#     Each item block: "<item_code>  <description>"  then size rows "02 X-Small 51 6.75 344.25"
#   • Totals: "TOTAL : 1,155 PIECES USD 7,796.25"
#   • Discount note and amount-in-words also in text
#   • Delivery details section has warehouse date, FOB port, packing method
#   • All text has spurious spaces within words — must compress before regex

def _denoise(s):
    """Remove letter-spacing from a single string (e.g. 'M J L WT' → 'MJLWT',
    'O P E N' → 'OPEN'). Works on isolated field values only."""
    # Collapse single spaces between letters (including digits when surrounded)
    return re.sub(r'(?<=[A-Za-z0-9]) (?=[A-Za-z0-9])', '', s).strip()

def extract_next(pdf_path):
    result = {
        "type":              "next",
        "brand":             "NEXT Sourcing Limited",
        "po_no":             "",
        "po_date":           "",
        "print_date":        "",
        "season":            "",
        "revision":          "",
        "vendor_name":       "",
        "vendor_address":    "",
        "payment_terms":     "",
        "terms_of_shipment": "",
        "buyer":             "",
        "delivery_to":       "",
        "contract_number":   "",
        "item_code":         "",
        "item_description":  "",
        "color":             "",
        "currency":          "USD",
        "discount_pct":      "",
        "total_qty":         "",
        "total_amount":      "",
        "amount_in_words":   "",
        "delivery_number":   "",
        "warehouse_date":    "",
        "fob_port":          "",
        "packing_method":    "",
        "shipping_method":   "",
        "po_items":          [],
    }

    all_items = []

    with pdfplumber.open(pdf_path) as pdf:
        pages_text = [page.extract_text() or "" for page in pdf.pages]
        full_text = "\n".join(pages_text)

    # Vendor: line 2 of page 1 is "TO : C A R NATION CREATIONS PVT. LTD.   PRINT DATE : ..."
    # Split at large whitespace gap and take left part
    vendor_lines = []
    for line in pages_text[0].split("\n"):
        if re.match(r'^TO\s*:', line):
            left = re.split(r'\s{3,}', line)[0]
            left = re.sub(r'^TO\s*:\s*', '', left)
            vendor_lines.append(_denoise(left))
        elif re.match(r'^#376', line):
            left = re.split(r'\s{3,}', line)[0]
            vendor_lines.append(_denoise(left))
        elif re.match(r'^COIMBATORE', line):
            left = re.split(r'\s{3,}', line)[0]
            vendor_lines.append(_denoise(left))
    result["vendor_name"]    = vendor_lines[0] if vendor_lines else ""
    result["vendor_address"] = ", ".join(vendor_lines[1:]) if len(vendor_lines) > 1 else ""

    # PO date: "0 6 / 1 1/2025" — take only the date part before right-column begins
    pd_m = re.search(r'P\.O\.DATE\s*:\s*([^\n]+)', full_text)
    if pd_m:
        # Left of first large gap
        raw_date = re.split(r'\s{3,}', pd_m.group(1))[0]
        result["po_date"] = _denoise(raw_date)

    # Print date: similarly
    prd_m = re.search(r'PRINT DATE\s*:\s*([^\n]+)', full_text)
    if prd_m:
        raw_pd = re.split(r'\s{3,}', prd_m.group(1))[0]
        result["print_date"] = _denoise(raw_pd)

    # Season: "S / S 2 026" → denoise
    sea_m = re.search(r'SEASON\s*:\s*([^\n]+)', full_text)
    if sea_m:
        result["season"] = _denoise(sea_m.group(1).strip())

    # Contract number: "K R 8 627139" — denoise full token
    cn_m = re.search(r'CONTRACT NUMBER\s*:\s*([^\n]+)', full_text)
    if cn_m:
        result["contract_number"] = _denoise(cn_m.group(1).strip())

    # Buyer: stop before "DELIVERY TO" (separated by large gap)
    bm = re.search(r'BUYER\s*:\s*(.+?)(?:\s{3,}|$)', full_text, re.MULTILINE)
    result["buyer"] = _denoise(bm.group(1)) if bm else ""

    # Delivery to: after "DELIVERY TO :"
    dt_m = re.search(r'DELIVERY TO\s*:\s*([^\n]+)', full_text)
    result["delivery_to"] = _denoise(dt_m.group(1).strip()) if dt_m else ""

    # Payment terms: "O P E N ACCOUNT"
    pt_m = re.search(r'PAYMENT TERMS\s*:\s*([^\n]+)', full_text)
    result["payment_terms"] = _denoise(pt_m.group(1).strip()) if pt_m else ""

    # FOB port: "F O B CHENNAI" → "FOB CHENNAI"
    fob_m = re.search(r'TERMS OF SHIPMENT\s*:\s*(F\s*O\s*B\s+[^\n]+)', full_text, re.IGNORECASE)
    result["fob_port"] = _denoise(fob_m.group(1).strip()) if fob_m else ""

    # Packing method: "F L A T PACK" — stop before SHIPPING METHOD gap
    pam = re.search(r'PACKING METHOD\s*:\s*(.+?)(?:\s{3,}SHIPPING|$)', full_text, re.MULTILINE)
    result["packing_method"] = _denoise(pam.group(1).strip()) if pam else ""

    # Shipping method: "S E A"
    shm = re.search(r'SHIPPING METHOD\s*:\s*([^\n]+)', full_text)
    result["shipping_method"] = _denoise(shm.group(1).strip()) if shm else ""

    # Warehouse date: "0 3 - F ebruary-2026"
    wh_m = re.search(r'AT-WAREHOUSE DATE\s*:\s*([^\n]+)', full_text)
    result["warehouse_date"] = _denoise(wh_m.group(1).strip()) if wh_m else ""

    # Item description: "Y96543  JACQ GEO PINK" — do NOT denoise (it's already clean)
    im = re.search(r'\n([A-Z]\d{5})\s+(.+)', full_text)
    if im:
        result["item_code"]        = im.group(1)
        result["item_description"] = im.group(2).strip()
    result["color"] = rfield(r'COLOR\s*:\s*([^\n]+)', flags=re.IGNORECASE)

    # Amount in words — capture full phrase and denoise
    aw = re.search(r'SAY TOTAL\s*:\s*(.+?)(?=OTHER TERMS|SHIPMENT|Page)', full_text, re.DOTALL)
    if aw:
        result["amount_in_words"] = _denoise(" ".join(aw.group(1).split()))


# ─────────────────────────────────────────────────────────────────────────────
# 7. DMART (Avenue Supermarts) EXTRACTOR
# ─────────────────────────────────────────────────────────────────────────────
#
# PDF has NO pdfplumber tables — all text-based.
# Single-item PO, but item row spans multiple text lines.
# Key fields:
#   Ship/Bill To block (left): buyer name, address, lat/long, CIN, GSTIN
#   Right block: PO#, PO Date, Expc.Delv.Dt
#   Vendor block: name, address, phone, email, GSTIN
#   Item table: sno, ean, delivery_dt, article_description, uom, case_lot,
#               boxes, qty, b_price, t, p, v, net_price,
#               sgst_pct, cgst_igst_pct, cess, l_price, mrp, t_value
#   Totals + amount in words

def extract_dmart(pdf_path):
    result = {
        "type":            "dmart",
        "brand":           "D-Mart (Avenue Supermarts)",
        "po_no":           "",
        "po_date":         "",
        "exp_delivery_dt": "",
        # Buyer (Ship/Bill To)
        "buyer_name":      "",
        "buyer_address":   "",
        "buyer_cin":       "",
        "buyer_gstin":     "",
        "buyer_email":     "",
        "buyer_contact":   "",
        # Vendor
        "vendor_name":     "",
        "vendor_address":  "",
        "vendor_phone":    "",
        "vendor_email":    "",
        "vendor_gstin":    "",
        # Items
        "po_items":        [],
        # Totals
        "total_qty":       "",
        "total_boxes":     "",
        "total_value":     "",
        "amount_in_words": "",
    }

    with pdfplumber.open(pdf_path) as pdf:
        text = ""
        for page in pdf.pages:
            text += (page.extract_text() or "") + "\n"

    def field(pattern, flags=0):
        m = re.search(pattern, text, flags)
        return m.group(1).strip() if m else ""

    # ── PO header ─────────────────────────────────────────────────────────────
    result["po_no"]           = field(r'PO #\s*([\w]+)')
    result["po_date"]         = field(r'PO Date\s+([\d\.]+)')
    result["exp_delivery_dt"] = field(r'Expc\.Delv\.Dt\s+([\d\.]+)')
    result["buyer_cin"]       = field(r'CIN\s*:\s*([\w]+)')
    result["buyer_gstin"]     = field(r'GSTIN\s*:\s*([\w]+)')
    result["buyer_email"]     = field(r'Email\s+(receiving\.\S+)')
    result["buyer_contact"]   = field(r'Attn\s+(.+)')

    # Buyer name and address (Ship/Bill To block)
    bm = re.search(r'Ship/Bill To\s+(.+?)\nBaroda GM DC\s+(.+?)\n(.+?)\n', text, re.DOTALL)
    if bm:
        result["buyer_name"]    = bm.group(1).strip()
        result["buyer_address"] = (bm.group(2).strip() + " " + bm.group(3).strip()).strip()

    # ── Vendor ────────────────────────────────────────────────────────────────
    vm = re.search(r'Vendor\s+(CARNATION.+?)\n(.+?)\n(.+?)(?:\nPhone)', text, re.DOTALL)
    if vm:
        result["vendor_name"]    = vm.group(1).strip()
        result["vendor_address"] = (vm.group(2).strip() + ", " + vm.group(3).strip()).strip(", ")
    result["vendor_phone"]   = field(r'Phone\s+([\d]+)')
    result["vendor_email"]   = field(r'Email\s+(akhilesh\S+)')
    result["vendor_gstin"]   = field(r'GSTIN\s+(33\w+)')

    # ── Items ─────────────────────────────────────────────────────────────────
    # Item line pattern (single line with all numeric fields):
    # "1 1003973445 T-SHIRT MPN POPCORN EA 36 2016 270.00 0.00 0.00 0.00 270.00 0.00 5.00 0.00 283.50 459.00 571536.00"
    # Description continues on next lines, delivery date embedded in second chunk
    items = []
    lines = text.split("\n")
    i = 0
    while i < len(lines):
        line = lines[i]
        # Match item start: sno EAN article_start UOM caselot qty b_price ...
        m = re.match(
            r'^(\d+)\s+([\d]{10})\s+(.+?)\s+(EA|PC|PCS|NOS)\s+(\d+)\s+(\d+)\s+'
            r'([\d\.]+)\s+([\d\.]+)\s+([\d\.]+)\s+([\d\.]+)\s+([\d\.]+)\s+'
            r'([\d\.]+)\s+([\d\.]+)\s+([\d\.]+)\s+([\d\.]+)\s+([\d\.]+)\s+([\d,\.]+)',
            line
        )
        if m:
            # Collect continuation lines for description (delivery dt, HSN, colour info)
            desc_extra = []
            j = i + 1
            while j < len(lines):
                nxt = lines[j].strip()
                if re.match(r'^\d+\s+\d{10}', nxt) or re.match(r'^(Total|Amount)', nxt):
                    break
                if nxt:
                    desc_extra.append(nxt)
                j += 1

            full_desc = m.group(3).strip()
            # delivery date is often on the first continuation line
            delivery_dt = ""
            if desc_extra:
                dm = re.match(r'^(\d{2}\.\d{2}\.\d{4})', desc_extra[0])
                if dm:
                    delivery_dt = dm.group(1)
                    desc_extra = desc_extra[1:]  # remove date line
            full_desc += " " + " ".join(desc_extra)
            full_desc = full_desc.strip()

            # Extract HSN code from description
            hsn = ""
            hm = re.search(r'HSN\s*Code\s*:\s*(\d+)', full_desc, re.IGNORECASE)
            if hm:
                hsn = hm.group(1)

            # Extract MRP from description "@NNN"
            mrp_desc = ""
            mrp_m = re.search(r'@(\d+)', full_desc)
            if mrp_m:
                mrp_desc = mrp_m.group(1)

            items.append({
                "sno":          m.group(1),
                "ean":          m.group(2),
                "description":  full_desc,
                "hsn":          hsn,
                "delivery_dt":  delivery_dt,
                "uom":          m.group(4),
                "case_lot":     m.group(5),
                "boxes":        "",          # boxes on next line after case_lot
                "qty":          m.group(6),
                "b_price":      m.group(7),
                "trade_disc":   m.group(8),
                "promo_disc":   m.group(9),
                "vol_disc":     m.group(10),
                "net_price":    m.group(11),
                "sgst_pct":     m.group(12),
                "cgst_igst_pct":m.group(13),
                "cess":         m.group(14),
                "l_price":      m.group(15),
                "mrp":          m.group(16),
                "t_value":      m.group(17),
            })
            i = j
            continue
        i += 1

    # boxes count — appears as standalone number after case_lot on next line
    # e.g. "36\n56" means case_lot=36, boxes=56
    boxes_m = re.search(r'EA\s+36\s+\n(\d+)', text)
    if boxes_m and items:
        items[0]["boxes"] = boxes_m.group(1)
    # fallback from text
    if not (items and items[0]["boxes"]):
        tb = re.search(r'Total boxes:\s*(\d+)', text)
        if tb and items:
            items[0]["boxes"] = tb.group(1)

    result["po_items"] = items

    # ── Totals ────────────────────────────────────────────────────────────────
    result["total_qty"]       = field(r'Total\s+([\d,]+)\b')
    result["total_boxes"]     = field(r'Total boxes:\s*(\d+)')
    result["total_value"]     = field(r'Total\b.+([\d,\.]+)\s*$', re.MULTILINE)
    result["amount_in_words"] = field(r'Amount in words\s+(.+)')

    return result


# ─────────────────────────────────────────────────────────────────────────────
# ENTRY POINT
# ─────────────────────────────────────────────────────────────────────────────

EXTRACTORS = {
    "1": ("Celio", extract_celio),
    "2": ("Jockey", extract_jockey),
    "3": ("Lifestyle", extract_lifestyle),
    "4": ("Rare Rabbit", extract_rare_rabbit),
    "5": ("BALMOHK Uruguay", extract_balmohk),
    "6": ("NEXT Sourcing", extract_next),
    "7": ("D-Mart", extract_dmart),
}

def main():
    if len(sys.argv) == 3:
        pdf_path = sys.argv[1]
        po_type  = sys.argv[2]
    else:
        # Hardcoded fallback for direct run
        pdf_path = r""
        po_type  = ""

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