var currentSlide = 1;
var contentSlides = "";
var totalSlides;
  
jQuery(document).ready(function($){

    $('.ddate_selected').hide();
	$('.ddate_day_selected').on('click', function() {
        $('#slideshow-holder').find('.ddate_day_option').hide();
        $(this).parent().find('.ddate_day_option').show();
        if ($(this).closest('li').is(':last-child') || $(this).closest('li').next().is(':last-child'))
        {
            $(this).parent().find('.ddate_day_option').css('bottom', '10px');
        }
        $('.slideshow-content').addClass('hideme');
        $(this).parents('.slideshow-content').addClass('showme');
        $("#slideshow-holder").addClass('moveoverflow');
    });
    $('.deli_date_top li').hover(function() {
        var i = $(this).find('.ddate_day_option');
        if($(i).css('display') == 'none'){
           $('#slideshow-holder').find('.ddate_day_option').hide();
           $('.slideshow-content').removeClass('hideme');
        $(this).parent('.slideshow-content').removeClass('showme');
        $("#slideshow-holder").removeClass('moveoverflow');
        }
    }, function() {
        var i = $(this).find('.ddate_day_option');
        if($(i).css('display') == 'none'){
           $('#slideshow-holder').find('.ddate_day_option').hide();
           $('.slideshow-content').removeClass('hideme');
        $(this).parents('.slideshow-content').removeClass('showme');
        $("#slideshow-holder").removeClass('moveoverflow');
        }


    });

    /* $('.ddate_day_selected').on('click', function() {
        $('#slideshow-holder').find('.ddate_day_option').hide();
        $(this).parent().find('.ddate_day_option').show();
        if ($(this).closest('li').is(':last-child') || $(this).closest('li').next().is(':last-child'))
        {
            $(this).parent().find('.ddate_day_option').css('bottom', '10px');
        }
    });
	$('.deli_date_top li').hover(function() {
        var i = $(this).find('.ddate_day_option');
        if($(i).css('display') == 'none'){
           $('#slideshow-holder').find('.ddate_day_option').hide();
        }
    }, function() {
        var i = $(this).find('.ddate_day_option');
        if($(i).css('display') == 'none'){
           $('#slideshow-holder').find('.ddate_day_option').hide();
        }
    }); */

    // loai bo phan nay vi da sua tot hon trong ddate.phtml
    // $('.ddate_day_option').on( 'click', 'a', function(e) {
        // e.preventDefault();
        // if (!$(this).hasClass('disablerow')) {
        //     var showdtimetext = $(this).text();
        //     var showddate = $(this).closest('li')
        //         .clone()    //clone the element
        //         .children('div') //select all the children
        //         .remove()   //remove all the children
        //         .end()  //again go back to selected element
        //         .text();

        //     showddate = showddate.replace(/\s/g, '').replace(")", ", ").replace("(", "");

        //     $('.deli_date').find('.ddate_day_selected').text('Select');
        //     $(this).closest('.option_slot_select').find('.ddate_day_selected').text(showdtimetext);

        //     document.getElementById('showddate:date').innerHTML = showddate;
        //     document.getElementById('showddate:dtime').innerHTML = showdtimetext;
        //     $('.ddate_selected').show();
        //     $(this).parent().hide();

        // } else {
        //     $(this).closest('.option_slot_select').find('.ddate_day_selected').text('Select');
        // }
    // });

    jQuery("#slideshow-previous").click(showPreviousSlide);
    jQuery("#slideshow-next").click(showNextSlide);
  
    var totalWidth = 0;
    totalSlides = 0;
    contentSlides = jQuery(".slideshow-content");
    contentSlides.each(function(i){
        totalWidth += this.clientWidth;
        totalSlides++;
    });
    jQuery("#slideshow-holder").width(350*totalSlides);
    jQuery("#slideshow-scroller").attr({
        scrollLeft: 0
    });
    updateButtons();

    jQuery(window).resize(function() {
        if (jQuery('.option_slot_select').is(':visible')) {
            jQuery('.delivery').css('cssText', 'width: '+ jQuery('#checkout-step-ddate').width() + 'px !important');
            jQuery('.deli_date_top').width(jQuery('#checkout-step-ddate').width());
            jQuery('.deli_title').width(jQuery('#checkout-step-ddate').width() - 2);
            jQuery('.first_column_header').width(jQuery('#checkout-step-ddate').width() - 2);
        }else{
            jQuery('.deli_date_top').removeAttr('style');
            jQuery('.deli_title').removeAttr('style');
            jQuery('.first_column_header').removeAttr('style');
            jQuery('.delivery').removeAttr('style');
        }
    })
});



function showPreviousSlide()
{
    currentSlide--;
    updateContentHolder();
    updateButtons();
}

function showNextSlide()
{
    currentSlide++;
    updateContentHolder();
    updateButtons();
}

function updateContentHolder()
{
    var scrollAmount = 0;
    var scrollAmountH = 0;
    contentSlides.each(function(i){
        if(currentSlide - 1 > i) {
            scrollAmount += this.clientWidth;
            scrollAmountH += this.clientHeight;
        }
    });
    if (jQuery('.option_slot_select').is(':visible')) {
        jQuery(".slideshow-content").animate({
            bottom: scrollAmountH
        }, 350);
    } else {
        jQuery("#slideshow-scroller").animate({
            scrollLeft: scrollAmount
        }, 350);
    }
}

function updateButtons()
{
    if(currentSlide < totalSlides) {
        jQuery("#slideshow-next").show();
    } else {
        jQuery("#slideshow-next").hide();
    }
    if(currentSlide > 1) {
        jQuery("#slideshow-previous").show();
    } else {
        jQuery("#slideshow-previous").hide();
    }
}

if(document.getElementById("min_date")!=null){
    var a = 0;
    jQuery("#ddate-trigger-picker").click(function(){
        if(a==0){
            document.getElementById("cont").style.display="block";
            a=1;
        }else{
            document.getElementById("cont").style.display="none";
            a=0;
        }
    });

    var min_date = parseInt(document.getElementById("min_date").value);
    var max_date = parseInt(document.getElementById("max_date").value);
    var d_saturday = parseInt(document.getElementById("d_saturday").value);
    var d_sunday = parseInt(document.getElementById("d_sunday").value);
    var special_day = document.getElementById("special_day").value;
    special_day = special_day.split(';');
    var DISABLE_DATES = new Array();
    var j=0;
    for(i=0;i<special_day.length;i++){
        if(special_day[i]!=""){
            var spt = special_day[i].split('-');
            DISABLE_DATES[j] = spt[0] + spt[1] + spt[2] + ":true";
            j++;
        }
    }
	
    var LEFT_CAL = Calendar.setup({
        cont: "cont",
        weekNumbers: true,
        selectionType: Calendar.SEL_MULTIPLE,
        showTime: 12,
        min: min_date,
        max: max_date,
        weekNumbers:true,
        disabled : function(date) {
            if(((date.getDay() == 6) && (d_saturday =="0")) || 
                ((date.getDay() == 0) && (d_sunday =="0"))){ 
                return true;
            }else{
                date = Calendar.dateToInt(date);
                return date in DISABLE_DATES;
            }
        }  
    });
	
    LEFT_CAL.addEventListener("onSelect", function(){
        var ta = document.getElementById("delivery_date");
        ta.value = this.selection.print("%Y-%m-%d %p").join("\n");
        document.getElementById("ddate:date").value = this.selection.print("%Y-%m-%d");
        document.getElementById("ddate:dtime").value = this.selection.print("%p");
    });
}
