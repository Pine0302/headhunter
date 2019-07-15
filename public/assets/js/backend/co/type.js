define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'co/type/index',
                    add_url: 'co/type/add',
                    edit_url: 'co/type/edit',
                    del_url: 'co/type/del',
                    multi_url: 'co/type/multi',
                    table: 'co_type',
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
                    /*    {field: 'id', title: __('Id')},*/
                        {field: 'name', title: __('Name')},
                      /*  {field: 'hot', title: __('Hot')},*/
                        {field: 'enable', title: '是否展示',searchList: {'1':"展示", '2': "隐藏"}, style: 'min-width:100px;'},
                  /*      {field: 'create_at', title: __('Create_at'), operate:false, addclass:'datetimerange'},
                        {field: 'update_at', title: __('Update_at'), operate:false, addclass:'datetimerange'},*/
                        //{field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate}
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