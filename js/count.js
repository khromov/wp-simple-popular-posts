function SimplePopularPosts_AddCount(id, endpoint)
{
    var xmlhttp;
    var params = "/?spp_count=1&spp_post_id=" + id + "&cachebuster=" +  Math.floor((Math.random() * 100000));

    if (window.XMLHttpRequest)
        xmlhttp = new XMLHttpRequest();
    else
        xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");

    xmlhttp.onreadystatechange = function()
    {
        if (xmlhttp.readyState == 4 && xmlhttp.status == 200)
        {
            //alert(xmlhttp.responseText);
        }
    };

    xmlhttp.open("GET", endpoint + params, true);
    xmlhttp.send();
}