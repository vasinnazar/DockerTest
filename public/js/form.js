$(function () {
    var no_kladr_subdivs = ["685"];
    (function () {
        var no_kladr = ($.inArray($.app.subdivision_id,no_kladr_subdivs)>=0)?true:false;
        console.log(no_kladr,$.app.subdivision_id);
        $.kladr.setDefault({
            parentInput: '#registrationResidence',
            verify: !no_kladr,
            validate: !no_kladr,
            withParent:true,
            select: select,
            sendBefore: sendBefore,
            check: check,
            checkBefore: checkBefore
        });
        //юридический
        var $zip = $('[name="zip"]'),
            $region = $('[name="address_region"]'),
            $district = $('[name="address_district"]'),
            $city = $('[name="address_city"]'),
            $street = $('[name="address_street"]'),
            $building = $('[name="address_house"]');
        $region.kladr('type', $.kladr.type.region);
        $district.kladr('type', $.kladr.type.district);
        $city.kladr('type', $.kladr.type.city);
        $city.kladr('typeCode', $.kladr.typeCode.city);
        $street.kladr('type', $.kladr.type.street);
        //685 кызыл
        if(no_kladr){
            $street.kladr('verify', false);
        }
        $building.kladr('type', $.kladr.type.building);
        $building.kladr('verify', false);
        $zip.kladrZip('#registrationResidence');
        if(no_kladr){
            $('#registrationResidence').find('[name*="address"]').prop('readonly',false);
        }
    })();
    (function () {
        var no_kladr = ($.inArray($.app.subdivision_id,no_kladr_subdivs)>=0)?true:false;
        $.kladr.setDefault({
            parentInput: '#factResidence',
            verify: !no_kladr,
            validate: !no_kladr,
            select: select,
            sendBefore: sendBefore,
            check: check,
            checkBefore: checkBefore
        });
        //фактический
        var $zip1 = $('[name="fact_zip"]'),
                $region1 = $('[name="fact_address_region"]'),
                $district1 = $('[name="fact_address_district"]'),
                $city1 = $('[name="fact_address_city"]'),
                $street1 = $('[name="fact_address_street"]'),
                $building1 = $('[name="fact_address_house"]');
        $region1.kladr('type', $.kladr.type.region);
        $district1.kladr('type', $.kladr.type.district);
        $city1.kladr('type', $.kladr.type.city);
        $city1.kladr('typeCode', $.kladr.typeCode.city);
        $street1.kladr('type', $.kladr.type.street);
        if(no_kladr){
            $street1.kladr('verify', false);
        }
        $building1.kladr('type', $.kladr.type.building);
        $building1.kladr('verify', false);
        $zip1.kladrZip('#factResidence');
        if(no_kladr){
            $('#factResidence').find('[name*="fact_address"]').prop('readonly',false);
        }
    })();
    
    $('#registrationResidence [name="address_city1"], #factResidence [name="fact_address_city1"]').kladr({
        oneString: true,
        limit: 50,
        select: function (data) {
            console.log(data);
            var pfx = '', holder = '#registrationResidence';
            if ($(this).attr('name') == 'fact_address_city1') {
                pfx = 'fact_';
                holder = '#factResidence';
            }
            $(holder).find('[name="'+pfx+'address_street"]').val('').change();
            
            $(this).val('').change();
            
            $(holder).find('[name="'+pfx+'address_city"],[name="'+pfx+'address_region"],[name="'+pfx+'address_district"]').val('').change();
            $(holder+' [name="'+pfx+'address_city1"]').removeAttr('data-kladr-id');
            
            if(data.contentType==$.kladr.type.region){
                $(holder+' [name="'+pfx+'address_region"]').val(data.name + ' ' + data.typeShort).change();
            } else if(data.contentType==$.kladr.type.district){
                $(holder+' [name="'+pfx+'address_district"]').val(data.name + ' ' + data.typeShort).change();
            } else if(data.contentType==$.kladr.type.street){
                $(holder+' [name="'+pfx+'address_street"]').val(data.name + ' ' + data.typeShort).change();
            } else if(data.typeShort=='г'){
                $(holder+' [name="'+pfx+'address_city"]').val(data.name + ' ' + data.typeShort).change();
            } else {
                $(holder+' [name="'+pfx+'address_city1"]').val(data.name + ' ' + data.typeShort).change();
                $(holder+' [name="'+pfx+'address_city1"]').attr('data-kladr-id',data.id);
            }
            
            if ('parents' in data) {
                data.parents.forEach(function (p) {
                    if (p.contentType == 'cityOwner') {
                        $(holder+' [name="'+pfx+'address_city"]').val(p.name + ' ' + p.typeShort).change();
                    }
                    if (p.contentType == 'region') {
                        $(holder+' [name="'+pfx+'address_region"]').val(p.name + ' ' + p.typeShort).change();
                    }
                    if (p.contentType == 'district') {
                        $(holder+' [name="'+pfx+'address_district"]').val(p.name + ' ' + p.typeShort).change();
                    }
                    if (p.contentType == 'city') {
                        if(data.typeShort=='г'){
                            $(holder+' [name="'+pfx+'address_city"]').val(p.name + ' ' + p.typeShort).change();
                        } else {
                            $(holder+' [name="'+pfx+'address_city1"]').val(p.name + ' ' + p.typeShort).change();
                            $(holder+' [name="'+pfx+'address_city1"]').attr('data-kladr-id',p.id);
                        }
                    }
                });
            }
            $(holder+' [name="'+pfx+'address_city1"]').attr('data-kladr-type', $.kladr.type.city);
        }
    });

    function setLabel($input, text) {
        text = text.charAt(0).toUpperCase() + text.substr(1).toLowerCase();
        $input.parent().find('label').text(text);
    }

    function showError($input, message) {
        if ($('#curUserDropdown').attr('data-isadmin')){
            removeError($input);
            return false;
        }
        var li = '<li>' + message + '</li>';
        $input.parent().addClass('has-error');
        if ($input.next('div.help-block.with-errors').length === 0) {
            $input.after('<div class="help-block with-errors"><ul class="list-unstyled">' + li + '</ul>');
        } else {
            $input.next('div.help-block.with-errors').children('ul').html(li);
        }
    }
    function removeError($input) {
        $input.removeClass('kladr-error');
        $input.parents('.form-group:first').removeClass('has-error');
        $input.next('div.help-block.with-errors').remove();
    }

    function select(obj) {
        $(this).val((obj.contentType != 'building') ? (obj.name + ' ' + obj.typeShort) : obj.name);
        var pfx = ($(this).attr('name') == 'fact_address_city')?'fact_':'';
        if(obj.contentType == $.kladr.type.city){
            $(this).parents('.row:first').find('[name="'+pfx+'address_street"]').val('').change();
            $(this).parents('.row:first').find('[name="'+pfx+'address_city1"]').attr('data-kladr-id','').val('').change();
        }
        setLabel($(this), obj.type);
        removeError($(this));
    }
    function sendBefore(obj) {
        obj.name = (obj.name.lastIndexOf(' ') >= 0)
                ? obj.name.substr(0, obj.name.lastIndexOf(' '))
                : obj.name;
        updateOtherFields($(this));
    }
    function check(obj) {
        var $input = $(this);
        if (obj) {
            $input.val((obj.contentType != 'building') ? (obj.name + ' ' + obj.typeShort) : obj.name);
            setLabel($input, obj.type);
            removeError($input);
        } else {
             showError($input, 'Введено неверно');
        }
    }
    function checkBefore() {
        var $input = $(this);
//        updateOtherFields($input);
        if (!$.trim($input.val())) {
            removeError($input);
            return false;
        }

    }
    function updateOtherFields($input) {
        var $holder = $input.parents('#factResidence,#registrationResidence'), fact = '', $form = $input.parents('form:first');
        if ($holder.attr('id') == 'factResidence') {
            fact = 'fact_';
        }
        if ($input.attr('name') == fact + 'address_region') {
            $holder.find('[name="' + fact + 'address_district"]').change();
//            $.kladr.setValues({
//                district: $holder.find('[name="' + fact + 'address_district"]').val()
//            }, $holder.attr('id'));
        } else if ($input.attr('name') == fact + 'address_city') {
            $holder.find('[name="' + fact + 'address_district"]').change();
            $holder.find('[name="' + fact + 'address_region"]').change();
//            $.kladr.setValues({
//                district: $holder.find('[name="' + fact + 'address_district"]').val(),
//                region: $holder.find('[name="' + fact + 'address_region"]').val()
//            }, $holder.attr('id'));
        } else if ($input.attr('name') == fact + 'address_street') {
//            console.log($holder.find('[name="' + fact + 'address_district"]').val(), $holder.find('[name="' + fact + 'address_region"]').val(), $holder.find('[name="' + fact + 'address_city"]').val());
            $holder.find('[name="' + fact + 'address_district"]').change();
            $holder.find('[name="' + fact + 'address_region"]').change();
            $holder.find('[name="' + fact + 'address_city"]').change();
//            $.kladr.setValues({
//                district: $holder.find('[name="' + fact + 'address_district"]').val(),
//                region: $holder.find('[name="' + fact + 'address_region"]').val(),
//                city: $holder.find('[name="' + fact + 'address_city"]').val()
//            }, $holder.attr('id'));
        } else if ($input.attr('name') == fact + 'address_building') {
            $.kladr.setValues({
                district: $holder.find('[name="' + fact + 'address_district"]').val(),
                region: $holder.find('[name="' + fact + 'address_region"]').val(),
                city: $holder.find('[name="' + fact + 'address_city"]').val(),
                street: $holder.find('[name="' + fact + 'address_street"]').val()
            }, $holder.attr('id'));
        }
    }
});