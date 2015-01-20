$( document ).ready(function() {
  
    if (basename(window.location.href).substr(0,8)=="home.php")
        {
        ActivateSlideshow();
        }
  
  });


var SlideshowImages = new Array();
var SlideshowLinks = new Array();
var SlideshowCurrent = -1;
var SlideshowActive=false;
var SlideshowTimer=0;

function RegisterSlideshowImage(image,resource)
    {
    SlideshowImages.push(image);
    SlideshowLinks.push(resource);
    }

function SlideshowChange()
    {
    if (SlideshowImages.length==0 || !SlideshowActive) {return false;}
    
    SlideshowCurrent++;        
    
    if (SlideshowCurrent>=SlideshowImages.length)
        {
        SlideshowCurrent=0;
        }
    
    jQuery('#UICenter').css('background-image','url(' + SlideshowImages[SlideshowCurrent] + ')');

    SlideshowTimer=window.setTimeout('SlideshowChange();',4000);
    
    return true;
    }

function ActivateSlideshow()
    {
    SlideshowCurrent=-1;
    SlideshowActive=true;
    SlideshowChange();
    
    jQuery('#Footer').hide();
    }
    
function DeactivateSlideshow()
    {
    jQuery('#UICenter').css('background-image','none');
    SlideshowActive=false;
    window.clearTimeout(SlideshowTimer);

    jQuery('#Footer').show();
    }

