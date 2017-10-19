/**
 * 应用App js业务逻辑
 * @author 蔡繁荣
 * @version 1.1.0 build 20171009
 */
$(document).ready(function(){

    App.bind_toggle_navigator_event();

    App.bind_sidebar_drop_event();

    App.bind_item_sort_event();

    $('.tooltip-help').tooltip();

    App.bind_item_actived_switch_event();

    App.bind_delete_item_event();

    App.bind_dropdown_menu_event();

    App.bind_item_form_event();

    App.bind_slide_modal_event();


    if(window.location.hash){
        $(window.location.hash).addClass('highlight-fade');
    }
});


/**
 * App工具类定义
 */
;function App(){
};

// 侧边菜单箭头事件绑定
App.bind_sidebar_drop_event = function(){
    $("#sidebar .sidebar-drop").click(function(){
        if(!$(this).parent().hasClass('expand')){
            $("#sidebar .sidebar-menu-item.expand").removeClass('expand');
        }

        $(this).parent().toggleClass('expand');
    });
}


// 绑定折叠侧边菜单栏事件
App.bind_toggle_navigator_event = function(){

    $("#toggle_navigator").click(function(){
        $("html").toggleClass('navigator-collapse');

        // 记住导航栏折叠状态
        var is_collapse = $("html").hasClass('navigator-collapse') ? 1 : 0;
        $.cookie('navigator_collapse', is_collapse, { expires: 30, path: '/' });
    });

}

// 绑定排序事件
App.bind_item_sort_event = function(){

    $("#item_list").delegate('.sort', 'click', function(){
        var $this = $(this);
        if($this.hasClass('desc')){
            $this.removeClass('desc').addClass('asc');
            // asc
            $("#order_by").val($this.data('order-by'));
            $("#direction").val('asc');
        }else{
            // if($this.hasClass('asc')){
            //     $this.removeClass('asc');
            //     // default
            //     $("#order_by").val('');
            //     $("#direction").val('');
            // }else{
                $this.removeClass('asc').addClass('desc');
                // desc
                $("#order_by").val($this.data('order-by'));
                $("#direction").val('desc');
            // }
        }

        $("#search_form").submit();
    });

}


App.bind_item_actived_switch_event = function(){

    var elems = Array.prototype.slice.call(document.querySelectorAll('.j_switch'));

    elems.forEach(function(checkbox_elem) {
      var switchery = new Switchery(checkbox_elem, { color: '#12bdce', size: 'small' });
    });

    $(document).delegate(".j_switch", "change", function(){

        var $this = $(this);
        var item_id = $this.data('item-id');
        var url     = $this.data('url');
        if(typeof(item_id) == 'undefined'){
            alert('parameter data-item-id is missing');
            return;
        }
        if(typeof(url) == 'undefined'){
            alert('parameter data-url is missing');
            return;
        }

        var checkbox_elem = $this.get(0);
        var is_actived = 0;
        if(checkbox_elem.checked){
            is_actived = 1;
        }

        var params = { };
        params['id']         = item_id;
        params['is_actived'] = is_actived;
        $.ajax({
            type: "POST",
            url: url,
            data: params,
            dataType: "json",
            success: function(json){
                if(json.status == 1){

                }else{
                    toastr.error(json.info);
                }
            }
        });
    });

}

// 绑定删除数据事件
App.bind_delete_item_event = function(){

    $(document).delegate('.j_delete_item', 'click', function(){
        var $this = $(this);
        var item_id = $this.data('item-id');
        var url     = $this.data('url');
        if(typeof(item_id) == 'undefined'){
            alert('parameter data-item-id is missing');
            return;
        }
        if(typeof(url) == 'undefined'){
            alert('parameter data-url is missing');
            return;
        }


        // TODO 优化弹出确定对话框样式
        if(confirm('确定删除？')){
            $.ajax({
                type: "POST",
                url: url,
                data: { id: item_id },
                dataType: "json",
                success: function(json){
                    if(json.status == 1){
                        $("#item_"+item_id).remove();

                        toastr.success(json.info);

                        // TODO 若为最后一个item, 则打印“暂无数据”
                    }else{
                        toastr.error(json.info);
                    }
                }
            });
        }
    })

}


// 绑定bootstrap dropdown下拉菜单点击事件
App.bind_dropdown_menu_event = function() {
    
    $(".dropdown-menu").delegate("a", "click", function(){
        var $this = $(this);
        var text  = $this.text();
        var value = $this.data('value');

        $this.closest('.dropdown-menu').prev("button").html(text+' <span class="caret"></span>');
        // $("#is_actived").val(value);
        $this.closest('.dropdown-menu').next("input").val(value);

        $("#search_form").submit();
    });

}


// 绑定数据表单提交事件
App.bind_item_form_event = function(){

    // 绑定添加表单事件
    $("#item_form").validate({
        submitHandler: function(form){
            var $btn = $("#primary_btn");
             
            // alert('表单提交');
            // form.submit(); //使用form.submit()，而不是$(form).submit();
            $(form).ajaxSubmit({
                type:"POST",
                datetype:"json",
                beforeSubmit: function(){
                    $btn.button('loading');
                },
                success:function(json){
                    if(json.status == 1){
                        toastr.success(json.info);

                        setTimeout(function(){
                            window.location.href = json.url;
                        }, 1000);
                    }else{
                        toastr.error(json.info);
                    }
                    $btn.button('reset');
                }
            });
        },
        errorElement: 'label',
        errorPlacement: function(error, element){
            $(element).parent().append(error);

            // 支持help-block的正确显示
            var $help_block = $(element).parent().find('.help-block');
            if($help_block.length == 1){
                $(element).data('help-block', $help_block);
                $help_block.remove();
            }
        },
        success: function(label, element){
            label.remove();

            // 支持help-block的正确显示
            var $help_block = $(element).data('help-block');
            if(typeof($help_block) != "undefined"){
                $(element).parent().append($help_block);
            }
        },
        rules: {
            // confirm_password: {
            //     equalTo: "#password"
            // }
        }
    });

}


App.bind_slide_modal_event = function(){

    // 侧滑模态框
    $('#slide_modal').on('show.bs.modal', function (event) {

        var $button = $(event.relatedTarget);
        var item_id = $button.data('item-id');
        var url     = $button.data('url');
        if(typeof(item_id) == 'undefined'){
            alert('parameter data-item-id is missing');
            return;
        }
        if(typeof(url) == 'undefined'){
            alert('parameter data-url is missing');
            return;
        }


        if($("html").hasClass('navigator-collapse')){
            scroll_top = $(document).scrollTop();
            // 0    60
            // 39   21
            // 60   0
            // 100  0
            if(scroll_top - 60 >= 0){
                unstable_top = 0;
            }else{
                unstable_top = 60 - scroll_top;
            }

            $("#slide_modal").css({ top: unstable_top });
        }

        // 初始化Offer详情页面
        $.ajax({
            type: "GET",
            url: url,
            data: { id: item_id },
            dataType: "json",
            success: function(json){
                if(json.status == 1){
                    var html = template.render('tpl_item_detail', {
                        data: json.data
                    });
                    $("#modal_content .modal-body").html(html);
                }
            }
        });
    });

    $('#slide_modal').on('shown.bs.modal', function (event) {

        if($("html").hasClass('navigator-collapse')){
            scroll_top = $(document).scrollTop();
            if(scroll_top - 60 >= 0){
                unstable_top = 0;
            }else{
                unstable_top = 60 - scroll_top;
            }

            $("#slide_modal .modal-backdrop").css({ top: unstable_top });
        }
    });

}