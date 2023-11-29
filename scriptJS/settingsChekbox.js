document.addEventListener('change',e =>{
    let id_orders
    const url  = 'http://h202142752.nichost.ru/local/production_schedule/scr/bitrix_request.php'
    if(e.target.checked && e.target.className === "work"){
        e.composedPath().find(function (item, index) {
            if (index === 1) {
                id_orders = item.querySelector('.id').innerHTML;
                arr = {
                    'id_orders': id_orders,
                    'flag': true
                }
                console.log(arr)
                post(url,arr )
            }
        })
    }else{
        e.composedPath().find(function (item, index) {
            if (index === 1) {
                id_orders = item.querySelector('.id').innerHTML;
                arr = {
                    'id_orders': id_orders,
                    'flag': false
                }
                console.log(arr)
                post(url,arr )
            }
        })
    }

})