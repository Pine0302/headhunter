define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 're/training/index',
                    add_url: 're/training/add',
                    edit_url: 're/training/edit',
                    del_url: 're/training/del',
                    multi_url: 're/training/multi',
                    table: 're_training',
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
                        {field: 'name', title: __('Name')},
                        {field: 'reTraintype.name', title: '所属类型'},
                        {field: 'max_person',  operate: false,title: '最大人数'},
                        {field: 'person_count',operate: false, title: '报名人数'},
                        {field: 'ch_status',operate: false, title: '活动状态'},
                        {field: 'status',visible:false,searchList:{'1':'报名人满结束','2':'报名中','3':'报名未满取消','4':'活动结束'}, title: '活动状态'},
                        {field: 'fee', title: '费用',operate:false},
                        {field: 'reward_up', title: '推荐佣金',operate:false},
                        {field: 'area', operate: false,title: __('Area')},
                        {field: 'address', operate: false, title: __('Address')},
                        {field: 'train_time',title:"开始时间", operate:'RANGE', operate: false, addclass:'datetimerange'},
                        {field: 'train_time_end',  title:"结束时间", operate:'RANGE',operate: false, addclass:'datetimerange'},
                        {field: 'sign_time',  title: "报名截止时间", operate:'RANGE',operate: false, addclass:'datetimerange'},
                        {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate}
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