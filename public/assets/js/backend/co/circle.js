define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'co/circle/index',
                    add_url: 'co/circle/add',
                    edit_url: 'co/circle/edit',
                    del_url: 'co/circle/del',
                    multi_url: 'co/circle/multi',
                    table: 'co_circle',
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
                        /*{field: 'id', title: __('Id')},*/
                        {field: 'coType.name', title: '类型名称'},
                        {field: 'name', title: __('Name')},
                        /*{field: 'pic', title: __('Pic')},*/
                        {field: 'tip_num',operate:false,  title: __('Tip_num')},
                        {field: 'enable', title: '是否展示',searchList: {'1':"展示", '2': "隐藏"}, style: 'min-width:100px;'},
                       /* {field: 'create_at', title: __('Create_at'), operate:false, addclass:'datetimerange'},
                        {field: 'update_at', title: __('Update_at'), operate:false, addclass:'datetimerange'},*/
                        {field: 'tip_update',operate:false,  title: __('Tip_update')},
                        {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: function(value, row, index){
                            var that = $.extend({}, this);
                            var table = $(that.table).clone(true);
                            $(table).data("operate-del", null);
                            that.table = table;
                            return Table.api.formatter.operate.call(that, value, row, index);
                        }
                        }
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

