$(document).ready(function () {

    setTimeout(function () {
        window.scrollTo(0, 0);
        $("#overlay").css("display", "none");
    }, 500);
});


async function logout() {
    const response = await method_get('./app/Auth/logout.php')

    if (response.status == true) {
        location.reload()
    } else {
        return;
    }
}