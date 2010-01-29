LegacyLinks = function(show_tooltip, window_type, bubble_path)
{
    var whois_pattern = new RegExp("http:\/\/whois\.domaintools.com\/(.*)", "i");
    var domain_pattern = new RegExp("([a-z0-9\-]+\.)+[a-z0-9\-]+", "i");
    var no_tags = new RegExp("<|>", "i");
    var idna = document.createElement("a");
    var target;
    var x;
    
    if(window_type == 'new')
    {
        target = '_blank';
    }
    else
    {
        target = '_self';
    }
    for (x = 0; x < document.links.length; x++)
    {
        if (whois_pattern.test(document.links[x].href))
        {
            var domain = RegExp.lastParen;
            var anchor = document.links[x].innerHTML;
            //bit of a hack to convert domain to idna if needed
            idna.href = "http://" + decodeURI(domain) + "/";
            domain = idna.hostname.toLowerCase();
            //if there are tags in the innerHTML of link then do not touch link
            if(domain_pattern.test(domain) && !no_tags.test(document.links[x].innerHTML))
            {
                if(document.links[x].className != "tooltip" && document.links[x].className != "dlink")
                {
                    var tooltip_url = "http://tooltips.domaintools.com/preview/v1.0/-/" + domain + "/";
                    var whois_url = "http://whois.domaintools.com/" + domain + "/";
                    if (show_tooltip == "yes")
                    {
                        var s = document.createElement("span");
                        s.innerHTML = '<a class="tooltip" onmouseover="tooltip_frm.update(\'' + tooltip_url + '\')" href="' + whois_url + '" rel="#tooltip_div" target="' + target + '"><img style="margin-left: 5px;" src="' + bubble_path + '" alt="' + domain + '"/></a>'; 
                        document.links[x].parentNode.insertBefore(s, document.links[x].nextSibling);
                    }
                    else
                    {
                        var s = document.createElement("span");
                        s.innerHTML = '<a class="dlink" href="' + whois_url + '" target="' + target + '"><img style="margin-left: 5px;" src="' + bubble_path + '" alt="' + domain + '"/></a>';
                        document.links[x].parentNode.insertBefore(s, document.links[x].nextSibling);
                    }
                }
            }
        }
    }
}
