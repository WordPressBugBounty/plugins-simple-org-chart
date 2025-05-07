jQuery(document).ready(function() {
    jQuery("#oreset").click(function(e) {
        var vs = confirm("This will reset chart with selected user on top. Ok?");

        if (vs == true) {
        } else {
            return false;
        }
    });

    jQuery("#org").jOrgChart({
        chartElement: "#chart",
        dragAndDrop: true
    });

    jQuery("#comboBox,  #user_dropdown").select2();

    /* WP Media Uploader */
    var _shr_media = true;
    var _orig_send_attachment = wp.media.editor.send.attachment;

    jQuery(".shr-image").click(function() {
        var button = jQuery(this),
            textbox_id = jQuery(this).attr("data-id"),
            image_id = jQuery(this).attr("data-src"),
            _shr_media = true;

        wp.media.editor.send.attachment = function(props, attachment) {
            if (_shr_media && attachment.type === "image") {
                if (image_id.indexOf(",") !== -1) {
                    image_id = image_id.split(",");
                    $image_ids = "";
                    jQuery.each(image_id, function(key, value) {
                        if ($image_ids) $image_ids = $image_ids + ",#" + value;
                        else $image_ids = "#" + value;
                    });

                    var current_element = jQuery($image_ids);
                } else {
                    var current_element = jQuery("#" + image_id);
                }

                jQuery("#" + textbox_id).val(attachment.id);
                //console.log(textbox_id);
                current_element.attr("src", attachment.url).show();
            } else {
                alert("Please select a valid image file");
                return false;
            }
        };

        wp.media.editor.open(button);
        return false;
    });

    var $links = jQuery(".jOrgChart").find("a");

    $links.on("click", function(e) {
        e.preventDefault();

        var group = jQuery(this)
            .attr("href")
            .replace("#", "");

        jQuery(".overlay1").hide();
        jQuery('.overlay1[data-id="' + group + '"]').show();
        jQuery('.overlay1[data-id="' + group + '"]').css("visibility", "visible");
        jQuery('.overlay1[data-id="' + group + '"]').css("opacity", 1);
    });

    jQuery("#btnAddOrg").click(function() {
        var name = jQuery("#comboBox option:selected").text();

        var val = jQuery("#comboBox option:selected").val();

        if (val != "") {
            var temp = val.split("*");

            name = temp[3];

            var img = temp[1];

            var org_role = temp[2];

            var name_actived = "";

            var val_f = "org";

            if (jQuery("ul#org > li > ul").length) {
               // console.log(1);
                jQuery("ul#org > li > ul").append(
                    '<li id="' +
                    temp[0] +
                    '"><img src="' +
                    img +
                    '" />' +
                    '<span id="' +
                    temp[0] +
                    '" class="name_c">' +
                    name +
                    "</span><small>" +
                    org_role +
                    "</small>"  +
                    '<a class="rmv-nd close" href="javascript:void(0);">Delete</a>' +
                    '</li>'
                );
            } else {
               // console.log(2);
                jQuery("ul#org > li").append(
                    '<ul id="2"><li id="' +
                    temp[0] +
                    '"><img src="' +
                    img +
                    '" />' +
                     +
                    '<span id="' +
                    temp[0] +
                    '" class="name_c">' +
                    name +
                    "</span><small>" +
                    org_role +
                    "</small>" +
                    '<a class="rmv-nd close" href="javascript:void(0);">Delete</a>' +
                    "</li></ul>"
                );
            }

            jQuery(".jOrgChart").remove();

            jQuery("#org").jOrgChart({ chartElement: "#chart", dragAndDrop: true });

            jQuery("#comboBox option:selected").remove();

            if (jQuery("#comboBox option:enabled").length === 0) {
                jQuery("#btnAddOrg").attr("disabled", true);
            }
        }
    });

    /*If not is removing, is conflict in jQuery.jOrgChart.js, line 227 --> Prevent trees collapsing if a link inside a node is clicked */
    jQuery(document).on("click", ".rmv-nd", function() {
        var id = jQuery(this)
            .parent()
            .find(".name_c")
            .attr("id");

        var hidden_val = jQuery("#hidden_val").val();

        var temp = hidden_val.split("$");

        for (var i = 0; i < temp.length; i++) {
            var temp_more = temp[i].split("*");

            if (temp_more[0] == id) {
                jQuery("#comboBox").append(
                    '<option value="' +
                    temp_more[0] +
                    "*" +
                    temp_more[1] +
                    "*" +
                    temp_more[2] +
                    "*" +
                    temp_more[3] +
                    '">' +
                    temp_more[3] +
                    "</option>"
                );
            }
        }

        var get_all_children = jQuery("#org #" + id)
            .children("ul")
            .html();

        if( typeof get_all_children !== 'undefined' || get_all_children !== null ){
            //console.log(get_all_children);
            jQuery("#org #" + id)
                .closest("ul")
                .append(get_all_children);
        }

        jQuery("#org #" + id).remove();
        jQuery(".jOrgChart").remove();

        jQuery("#org").jOrgChart({ chartElement: "#chart", dragAndDrop: true });
    });

    jQuery(".node-cell .node").on("click", function() {
        jQuery(".node-cell .node").removeClass("actived");
        jQuery(this).addClass("actived");
    });
});

function makeArrays() {
    var hierarchy = [];

    jQuery("#org li").each(function() {
        var uid = jQuery(this).attr("id");
        var name = jQuery(this)
            .clone()
            .children()
            .remove()
            .end()
            .text();
        var hidSTR = "";
        var hid = jQuery(this).parents("li");
        if (hid.length == 0) {
            //If this object is the root user, substitute id with "orgName" so the DB knows it's the name of organization and not a user
            hidSTR = "";
            var user = new Object();
            user[uid] = hidSTR;
            hierarchy.push(user);
        } else {
            for (var i = hid.length - 1; i >= 0; i--) {
                if (i != hid.length - 1) {
                    hidSTR = hid[i].id;
                } else {
                    hidSTR = hid[i].id;
                }
            }

            var user = new Object();
            user[uid] = hidSTR;
            hierarchy.push(user);
        }
    });

    saveArray(hierarchy);

   // console.log(hierarchy);
}

function saveArray(hierarchy) {
    var jsonString = JSON.stringify(hierarchy);
    jQuery.ajax({
        url: ajaxurl,
        type: "POST",

        data: {action: "org_chart", tree: hierarchy},

        success: function (data) {
            jQuery(".chart_saved").fadeIn();
            setTimeout(function () {
                jQuery(".chart_saved").fadeOut();
            }, 2000);
        }
    });
}