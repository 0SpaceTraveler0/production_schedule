function createTableBody(data) {
    console.log(data)
    data.forEach((item, index) => {
        let trElement = document.createElement("tr");
        trElement.id = Math.random();

        let td_id = document.createElement("td");
        td_id.className = 'id';
        let id = item['id']

        let td_combined_passport = document.createElement("td");
        let combined_passport = item['combined_id_passport']

        let td_customer = document.createElement("td");
        let customer = item['customer']

        let td_date = document.createElement("td");
        let date = item['shipping_date']

        let td_efficiency = document.createElement("td");
        let efficiency = item['efficiency'].toFixed(2) + "%";

        let td_width = document.createElement("td");
        let width = item['width'];

        let td_material = document.createElement("td");
        let material = item['material']

        let td_items_per_plan = document.createElement("td");
        let items_per_plan = item['items_per_plan']
        
        let td_remaining_quantity = document.createElement("td");
        let remaining_quantity = item['remaining_quantity']

        let td_remaining_quantity_copy_order = document.createElement("td");
        let remaining_quantity_copy_order = item['remaining_quantity_copy_order']

        let td_main_order_quantity_widtht = document.createElement("td");
        let main_order_quantity_widtht = item['main_order_quantity_widtht']

        let td_combined_order_quantity_widtht = document.createElement("td");
        let combined_order_quantity_widtht = item['combined_order_quantity_widtht']

        let td_img = document.createElement("td");
        
        let img = ' ';
        /*check_box.type="checkbox";
        check_box.className="work";*/
        if(item['urgent'] == '1'){
            img = '&#128293';
        }

        td_id.innerHTML = id;
        td_combined_passport.innerHTML = combined_passport;
        td_customer.innerHTML = customer;
        td_date.innerHTML = date;
        td_efficiency.innerHTML = efficiency;
        td_width.innerHTML = width;
        td_material.innerHTML = material;
        td_items_per_plan.innerHTML =items_per_plan;
        td_remaining_quantity.innerHTML = remaining_quantity;
        td_remaining_quantity_copy_order.innerHTML = remaining_quantity_copy_order;
        td_main_order_quantity_widtht.innerHTML = main_order_quantity_widtht;
        td_combined_order_quantity_widtht.innerHTML = combined_order_quantity_widtht;
        td_img.innerHTML = img;


        trElement.appendChild(td_id);
        trElement.appendChild(td_combined_passport);
        trElement.appendChild(td_customer);
        trElement.appendChild(td_date);
        trElement.appendChild(td_efficiency);
        trElement.appendChild(td_width);
        trElement.appendChild(td_material);
        trElement.appendChild(td_items_per_plan);
        trElement.appendChild(td_remaining_quantity);
        trElement.appendChild(td_remaining_quantity_copy_order);
        trElement.appendChild(td_main_order_quantity_widtht);
        trElement.appendChild(td_combined_order_quantity_widtht);
        trElement.appendChild(td_img);

        let x = document.getElementById("tbd");

        x.appendChild(trElement);
    })
}