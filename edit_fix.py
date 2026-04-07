import re

file_path = r'c:\xampp\htdocs\Wijdan\resources\views\admin_panel\purchase\edit.blade.php'
with open(file_path, 'r', encoding='utf-8') as f:
    content = f.read()

# Replace Title
content = content.replace('Create Purchase', 'Edit Purchase')

# Replace Form Action
content = content.replace(
    '<form action=\"{{ route(\'store.Purchase\') }}\" method=\"POST\">',
    '<form action=\"{{ route(\'purchase.update\', ->id) }}\" method=\"POST\">\\n                                            @method(\'PUT\')'
)

# Date array
content = content.replace(
    'name=\"purchase_date\"\\n                                                                value=\"{{ date(\'Y-m-d\') }}\"',
    'name=\"purchase_date\"\\n                                                                value=\"{{ ->purchase_date ? \Carbon\Carbon::parse(->purchase_date)->format(\'Y-m-d\') : date(\'Y-m-d\') }}\"'
)

# Vendor loop
content = content.replace(
    '<option value=\"{{ ->id }}\">{{ ->name }}</option>',
    '<option value=\"{{ ->id }}\" {{ ->vendor_id == ->id ? \'selected\' : \'\' }}>{{ ->name }}</option>'
)

# Inv No
content = content.replace(
    'name=\"purchase_order_no\" type=\"text\" class=\"form-control\"',
    'name=\"invoice_no\" type=\"text\" class=\"form-control\" value=\"{{ ->invoice_no }}\"'
)

# Purchase To Radios
content = content.replace(
    'id=\"purchaseWarehouse\">',
    'id=\"purchaseWarehouse\" {{ ->purchase_to == \'warehouse\' ? \'checked\' : \'\' }}>'
)
content = content.replace(
    'id=\"purchaseShop\">',
    'id=\"purchaseShop\" {{ ->purchase_to == \'shop\' ? \'checked\' : \'\' }}>'
)

# Warehouse Box
content = content.replace(
    'class=\"col-xl-4 col-sm-6 d-none\" id=\"purchaseWarehouseBox\"',
    'class=\"col-xl-4 col-sm-6 {{ ->purchase_to == \'warehouse\' ? \'\' : \'d-none\' }}\" id=\"purchaseWarehouseBox\"'
)
content = content.replace(
    '<option value=\"{{ ->id }}\">{{ ->warehouse_name }}</option>',
    '<option value=\"{{ ->id }}\" {{ ->warehouse_id == ->id ? \'selected\' : \'\' }}>{{ ->warehouse_name }}</option>'
)

# Notes
content = content.replace(
    'name=\"note\" type=\"text\" class=\"form-control\"',
    'name=\"note\" type=\"text\" class=\"form-control\" value=\"{{ ->note }}\"'
)
content = content.replace(
    'name=\"job_description\" type=\"text\" class=\"form-control\"',
    'name=\"job_description\" type=\"text\" class=\"form-control\" value=\"{{ ->job_description }}\"'
)

# Subtotal, Discount, Extra cost, Net amount
content = content.replace(
    'id=\"subtotal\" class=\"form-control\"\\n                                                        value=\"0\"',
    'id=\"subtotal\" class=\"form-control\"\\n                                                        value=\"{{ ->subtotal }}\"'
)
content = content.replace(
    'name=\"discount\" value=\"0\"',
    'name=\"discount\" value=\"{{ ->discount }}\"'
)
content = content.replace(
    'name=\"extra_cost\" value=\"0\"',
    'name=\"extra_cost\" value=\"{{ ->extra_cost }}\"'
)
content = content.replace(
    'class=\"form-control fw-bold\" value=\"0\"',
    'class=\"form-control fw-bold\" value=\"{{ ->net_amount }}\"'
)


# Replace existing dummy row with foreach
# We can find <tbody id="purchaseItems"> ... </tbody> and replace it
tbody_start = content.find('<tbody id=\"purchaseItems\">')
tbody_end = content.find('</tbody>', tbody_start) + len('</tbody>')

new_tbody = '''<tbody id=\"purchaseItems\">
    @foreach(->items as )
    <tr>
        <td class=\"product-col\">
            <input type=\"hidden\" name=\"product_id[]\" class=\"product_id\" value=\"{{ ->product_id }}\">
            <input type=\"text\" class=\"form-control productSearch\" placeholder=\"Select product...\" value=\"{{ ->product->item_name ?? \'\' }}\" readonly>
        </td>
        <td class=\"item_code border\"><input type=\"text\" name=\"item_code[]\" class=\"form-control\" value=\"{{ ->product->item_code ?? \'\' }}\" readonly></td>
        <td class=\"uom border\"><input type=\"text\" name=\"uom[]\" class=\"form-control\" value=\"{{ ->product->brand->name ?? \'\' }}\" readonly></td>
        <td class=\"unit border\"><input type=\"text\" name=\"unit[]\" class=\"form-control\" value=\"{{ ->unit }}\" readonly></td>
        <td><input type=\"number\" step=\"0.01\" name=\"price[]\" class=\"form-control price\" value=\"{{ ->price }}\"></td>
        <td>
            <div class=\"d-flex gap-1 align-items-center\" style=\"min-width:140px;\">
                <input type=\"number\" step=\"0.01\" name=\"item_disc[]\" class=\"form-control item_disc\" placeholder=\"PKR\" style=\"width:60%;\" value=\"{{ ->item_discount }}\">
                <div style=\"width:40%; display:flex; align-items:center;\">
                    <input type=\"number\" step=\"0.01\" name=\"item_disc_pct[]\" class=\"form-control item_disc_pct\" placeholder=\"%\" style=\"width:70%;\">
                    <span style=\"width:30%; text-align:center;\">%</span>
                </div>
            </div>
        </td>
        <td class=\"qty\"><input type=\"number\" name=\"qty[]\" class=\"form-control quantity\" min=\"1\" value=\"{{ ->qty }}\"></td>
        <td><input type=\"text\" name=\"item_note[]\" class=\"form-control\" placeholder=\"Optional note\" value=\"{{ ->note ?? \'\' }}\"></td>
        <td class=\"total border\"><input type=\"text\" name=\"line_total[]\" class=\"form-control row-total\" value=\"{{ ->line_total }}\" readonly></td>
        <td><button type=\"button\" class=\"btn btn-sm btn-danger remove-row\">X</button></td>
    </tr>
    @endforeach
</tbody>'''

content = content[:tbody_start] + new_tbody + content[tbody_end:]

content = content.replace(\"Save Purchase\", \"Update Purchase\")
content = content.replace(\"action='{{ route('store.Purchase') }}'\", \"action='{{ route('purchase.update', ->id) }}'\")

with open(file_path, 'w', encoding='utf-8') as f:
    f.write(content)

print('File updated successfully.')
