async function postData(url = '',date) {
    
    const arrayBody = {'dateFrom': date[0],'dateTo': date[1]}
    const response = await fetch(url, {
        method: 'POST',
        body: JSON.stringify(arrayBody),
        headers: {
            'Accept': 'application/json',
            'Content-Type': 'application/json'
        }
    }).catch (function (error) {
        console.log('Request failed', error);
    });
    return await response.json(); // parses JSON response into native JavaScript objects
}

function funonload(url,dateFrom,dateTo) {
    postData(url,dateFrom,dateTo)
        .then((data) => {
            for (; document.getElementById('table').getElementsByTagName('tr').length > 1; ) {
                document.getElementById('table').deleteRow(1);
            }


            sortData(data);
            createTableBody(data);
        })
}


