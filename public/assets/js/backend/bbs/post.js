define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'bbs/post/index',
                    add_url: 'bbs/post/add',
                 //   edit_url: 'bbs/post/edit',
                    detail_url: 'bbs/post/detail',
                    pass_url: 'bbs/post/pass',
                    deny_url: 'bbs/post/deny',
                    online_url: 'bbs/post/online',
                    offline_url: 'bbs/post/offline',
                    del_url: 'bbs/post/del',
                    multi_url: 'bbs/post/multi',
                    table: 'bbs_post',
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'id',
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', title: __('Id')},
                        /*{field: 'user_id', title: __('User_id')},*/
                        /*{field: 'bbsForum.name', title: '板块名称',operate:false,operate:false},*/
                        /*{field: 'type', title: __('Type')},*/
                        {field: 'bbsMember.nick_name', title: "会员昵称"},
                    /*    {field: 'title', title: __('Title')},*/
                        {field: 'address', title: __('Address')},
                      /*  {field: 'latitude', title: __('Latitude')},
                        {field: 'longitude', title: __('Longitude')},*/
                        {field: 'status', title: __('Status')},
                        {field: 'thumb_up_count', title: __('Thumb_up_count')},
                        {field: 'message_count', title: __('Message_count')},
                        {field: 'view_count', title: __('View_count')},
                        {field: 'recommend', title : "是否推荐", operate:false},
                        /*{field: 'recommend_text', title: __('Recommend'), operate:false},*/
                        {field: 'create_time', title: __('Create_time'), operate:'RANGE', addclass:'datetimerange'},
                        {field: 'update_time', title: __('Update_time'), operate:'RANGE', addclass:'datetimerange'},
                        {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: function (value, row, index) {
                            var table = this.table;
                            // 操作配置
                            var options = table ? table.bootstrapTable('getOptions') : {};
                            // 默认按钮组
                            var buttons = $.extend([], this.buttons || []);
                            if (options.extend.edit_url !== '') {
                                buttons.push({
                                    name: 'edit',
                                    icon: 'fa fa-exchange',
                                    title: '编辑状态',
                                    extend: 'data-toggle="tooltip"',
                                    classname: 'btn btn-xs btn-primary btn-editone',
                                    // url: options.extend.detail_url
                                });
                            }

                            if (options.extend.detail_url !== '') {
                                buttons.push({
                                    name: 'detail',
                                    icon: 'fa fa-list',
                                    title: '详情',
                                    extend: 'data-toggle="tooltip"',
                                    classname: 'btn btn-xs btn-primary btn-detailone',
                                    // url: options.extend.detail_url
                                });
                            }

                            if (options.extend.del_url !== '') {
                                buttons.push({
                                    name: 'del',
                                    icon: 'fa fa-trash',
                                    title: __('Del'),
                                    extend: 'data-toggle="tooltip"',
                                    classname: 'btn btn-xs btn-danger btn-delone'
                                });
                            }
                            if(row.status=='隐藏'){
                                if (options.extend.pass_url !== '') {
                                    buttons.push({
                                        name: 'pass',
                                        icon: 'fa fa-check',
                                        title:'展示',
                                        extend: 'data-toggle="tooltip"',
                                        classname: 'btn btn-xs btn-primary btn-passone',
                                    });
                                }
                            }
                            if(row.status=='展示'){
                                if (options.extend.deny_url !== '') {
                                    buttons.push({
                                        name: 'pass',
                                        icon: 'fa fa-times',
                                        title:'隐藏',
                                        extend: 'data-toggle="tooltip"',
                                        classname: 'btn btn-xs btn-primary btn-denyone',
                                    });
                                }
                            }


                            if(row.recommend=='推荐'){
                                if (options.extend.offline_url !== '') {
                                    buttons.push({
                                        name: 'pass',
                                        icon: 'fa fa-ban',
                                        title:'取消推荐',
                                        extend: 'data-toggle="tooltip"',
                                        classname: 'btn btn-xs btn-primary btn-offlineone',
                                    });
                                }
                            }

                            if(row.recommend=='不推荐'){
                                if (options.extend.offline_url !== '') {
                                    buttons.push({
                                        name: 'pass',
                                        icon: 'fa fa-hand-grab-o',
                                        title:'推荐',
                                        extend: 'data-toggle="tooltip"',
                                        classname: 'btn btn-xs btn-primary btn-onlineone',
                                    });
                                }
                            }



                            return Table.api.buttonlink(this, buttons, value, row, index, 'operate');
                        }}
                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);
        },
        add: function () {
            Controller.api.bindevent();
        },
        edit: function () {
            Controller.api.bindevent();
        },
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            }
        }
    };
    return Controller;
});