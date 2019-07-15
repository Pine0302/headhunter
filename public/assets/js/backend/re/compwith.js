define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 're/compwith/index',
                    add_url: 're/compwith/add',
                    edit_url: 're/compwith/edit',
                    detail_url: 're/compwith/detail',
                    del_url: 're/compwith/del',
                    multi_url: 're/compwith/multi',
                    pass_url: 're/compwith/pass',
                    deny_url: 're/compwith/deny',
                    table: 're_compaccountdetail',
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
                      /*  {field: 're_company_id', title: __('Re_company_id')},*/
                        {field: 'reCompany.name', title: '公司名称'},
                        {field: 'cash', title: '金额', operate:'BETWEEN'},
       /*                 {field: 'way', title:'存取方向', searchList: {'1':"存入", '2': "取出"}, style: 'min-width:100px;'},*/
                        {field: 'method', operate:false,title: "具体操作"},
                        {field: 'materia',searchList: {'1':"支付宝", '2': "微信支付"}, style: 'min-width:100px;',operate:false, title: "存取方式"},
                        {field: 'create_at', title: "申请时间", operate:false, addclass:'datetimerange'},
                        {field: 'status', title:'状态' ,searchList: {'1':"已提交", '3': "已完成",'4':'已拒绝'}},
                        {field: 'operate', title: __('Operate'), table: table, buttons: [
                        ], events: Table.api.events.operate, formatter:  function (value, row, index) {
                            var table = this.table;
                            // 操作配置
                            var options = table ? table.bootstrapTable('getOptions') : {};
                            // 默认按钮组

                          /*  options.queryParams = function (params) {
                                return {
                                    search: params.search,
                                    sort: params.sort,
                                    order: params.order,
                                    filter: JSON.stringify({way: 2}),
                                    op: JSON.stringify({way: '='}),
                                    offset: params.offset,
                                    limit: params.limit,
                                };
                            };*/


                            var buttons = $.extend([], this.buttons || []);
                            if(row.showtype==1){
                                if (options.extend.pass_url !== '') {
                                    buttons.push({
                                        name: 'pass',
                                        icon: 'fa fa-check',
                                        title:'完成',
                                        extend: 'data-toggle="tooltip"',
                                        classname: 'btn btn-xs btn-primary btn-passone',
                                    });
                                }

                                if (options.extend.deny_url !== '') {
                                    buttons.push({
                                        name: 'deny',
                                        icon: 'fa fa-arrows',
                                        title:'拒绝',
                                        extend: 'data-toggle="tooltip"',
                                        classname: 'btn btn-xs btn-danger btn-denyone',
                                    });
                                }


                            }else{
                                if (options.extend.dragsort_url !== '') {
                                    buttons.push({
                                        name: 'dragsort',
                                        icon: 'fa fa-arrows',
                                        title: __('Drag to sort'),
                                        extend: 'data-toggle="tooltip"',
                                        classname: 'btn btn-xs btn-primary btn-dragsort'
                                    });
                                }
                                if (options.extend.edit_url !== '') {
                                    buttons.push({
                                        name: 'detail',
                                        icon: 'fa fa-list',
                                        title: '详情',
                                        extend: 'data-toggle="tooltip"',
                                        classname: 'btn btn-xs btn-primary btn-dialog',
                                        url: options.extend.detail_url
                                    });
                                }
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

$(function(){
/*   var options = table.bootstrapTable('getOptions');
    options.pageNumber = 1;
    options.queryParams = function (params) {
        return {
            search: params.search,
            sort: params.sort,
            order: params.order,
            filter: JSON.stringify({category_id: 1}),
            op: JSON.stringify({category_id: '='}),
            offset: params.offset,
            limit: params.limit,
        };
    };
    table.bootstrapTable('refresh', {});
    Toastr.info("当前执行的是自定义搜索");
    return false;*/
})


