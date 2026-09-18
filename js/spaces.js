const loadingMessage = document.getElementById("loadingMessage");
const emptyMessage = document.getElementById("emptyMessage");
const spacesGrid = document.getElementById("spacesGrid");

fetch("php/List_spaces.php")
    .then(response => response.json())
    .then(data => {
        console.log(data);
    });
