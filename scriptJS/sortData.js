function sortData(data) {
    data.forEach((item, index, arr) => {
        first = item;
        arr.splice(index, 1);
        if(item['id'] == 82 || item['combined_id_passport'] == 82){
            data.forEach((item_copy, index_copy,) => {
                if(item['width'] == item_copy['width'] && item_copy['flag'] != 1){
                    if(item['remaining_quantity'] > 0){
                        if(item['id'] == item_copy['id'] && item['main_order_quantity_widtht'] == item_copy['main_order_quantity_widtht']
                            || item['id'] == item_copy['combined_id_passport'] && item['main_order_quantity_widtht'] == item_copy['combined_order_quantity_widtht']){
                            item_copy['flag'] = 1
                            arr.splice(index , 0, item_copy);
                            arr.splice(index_copy+1, 1);
                        }
                    }else{
                        if(item['combined_id_passport'] == item_copy['combined_id_passport'] && item['combined_order_quantity_widtht'] == item_copy['combined_order_quantity_widtht']
                            || item['combined_id_passport'] == item_copy['id'] && item['combined_order_quantity_widtht'] == item_copy['main_order_quantity_widtht']){
                            item_copy['flag'] = 1
                            arr.splice(index , 0, item_copy);
                            arr.splice(index_copy+1, 1);
                        }
                    }
                }
            });
        }

        arr.splice(index, 0, first);
    });
}