define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 're/resume/index',
                    add_url: 're/resume/add',
                 /*   edit_url: 're/resume/edit',*/
                    del_url: 're/resume/del',
                    multi_url: 're/resume/multi',
                    table: 're_resume',
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
                        {field: 'id', title: "id"},
                       /* {field: 'user_id', title: __('User_id')},*/
                        {field: 'name', title: __('Name')},
                        {field: 'sex', title: __('Sex'),operate:false},
                        {field: 'birthday', title: __('Birthday'), operate:'RANGE', addclass:'datetimerange'},
                        {field: 'mobile', title: __('Mobile')},
                       /* {field: 'email', title: __('Email')},*/
                        /*{field: 'user_address', title: __('User_address'),operate:false},*/
                        /*{field: 'nationality', title: __('Nationality')},*/
                        {field: 'identity', title: __('Identity'), visible:false, searchList: {"1":"职场","2":"应届生"}},
                        {field: 'identity_text', title: __('Identity'), operate:false},
                        /*{field: 'id_num', title: __('Id_num')},*/
                        {field: 'education', title: __('Education'),visible:false, searchList: {"1":"无","2":"小学","3":"初中","4":"高中","5":"大专","6":"本科","7":"硕士","8":"博士","9":"博士后","10":"其他"}},
                        {field: 'education_text', title: __('Education'), operate:false},
                        {field: 'work_begin_time', title: __('Work_begin_time'), operate:'RANGE', addclass:'datetimerange'},
                       /* {field: 'work_years', title: __('Work_years')},*/
                        {field: 'title', title: __('Title')},
                        {field: 'label', title: __('Label')},
                        {field: 'mini_salary', title: __('Mini_salary'), operate:'BETWEEN'},
                        {field: 'max_salary', title: __('Max_salary'), operate:'BETWEEN'},
                     /*   {field: 're_job_id', title: __('Re_job_id')},*/
                        /*{field: 'job_name', title: __('Job_name')},
                        {field: 'city_code', title: __('City_code')},
                        {field: 'city_name', title: __('City_name')},*/
                        {field: 'will_city_code', title: __('Will_city_code')},
                        {field: 'will', title: __('Will'), visible:false, searchList: {"1":"积极找工作","2":"暂时不换工作","3":"随便看看"}},
                        {field: 'will_text', title: __('Will'), operate:false},
                        {field: 'intime', title: __('Intime'), visible:false,searchList: {"1":"随时","2":"两周以内","3":"2周-一个月","4":"1-3个月"}},
                        {field: 'intime_text', title: __('Intime'),operate:false},
                        {field: 'nature', title: __('Nature'), visible:false, searchList:{"1":"短期兼职","2":"长期兼职","3":"全职"}},
                        {field: 'nature_text', title: __('Nature'), operate:false},
                        /*{field: 'create_at', title: __('Create_at'), operate:'RANGE', addclass:'datetimerange'},
                        {field: 'update_at', title: __('Update_at'), operate:'RANGE', addclass:'datetimerange'},*/
                        {field: 'type', title: __('Type'), visible:false, searchList: {"1":"普通简历","2":"金边简历"}},
                        {field: 'type_text', title: __('Type'), operate:false},
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