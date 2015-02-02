$(document).ready(function() {
        $.validator.addClassRules("itemint", {
                required: false,
                digits: true,
                max: 9
        });
        $("#qform").validate();

        $(".close").click(function() {
                $(this).parent().toggleClass("hide");
                return false;
        });

        $("#newscene").click(function() {
                $('#divscene').toggleClass("hide");
                return false;
        });

        // mod2
        $("a.questext, a.moretext ").hover(
                function() {
                        $( this ).children("span.hide").stop().fadeIn( 400 );
                },
                function () {
                        $( this ).children("span.hide").stop().fadeOut( 200 );
                }
        );

        $("#language").click(function() {
                $("#lang").toggleClass("hide");
                return false;
        });
});
