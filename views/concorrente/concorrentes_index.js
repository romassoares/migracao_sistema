// $(document).ready(function () {
//     getLayout()
// })
// async function getLayout() {
//     const response = await method_get('./app/Layout/get_layouts.php')

//     if (response.status == true) {
//         montaDataPrinc(response.result)
//     } else {
//         // modal_error(response.msg)
//     }
// }

// function montaDataPrinc(data) {
//     if ($.fn.DataTable.isDataTable('#table_layout'))
//         $('#table_layout').DataTable().clear().destroy();

//     $('#table_layout').DataTable({
//         language: {
//             url: "//cdn.datatables.net/plug-ins/1.13.6/i18n/pt-BR.json",
//             oPaginate: {
//                 sNext: "<i class='bi bi-chevron-right'></i>",
//                 sPrevious: "<i class='bi bi-chevron-left'></i>",
//                 sLast: "<i class='bi bi-chevron-double-right'></i>",
//                 sFirst: "<i class='bi bi-chevron-double-left'></i>",
//             },
//             sInfo: "_START_ a _END_ de _TOTAL_ registros",
//             sLengthMenu: " Exibindo _MENU_",
//             sInfoFiltered: "",
//             sInfoPostFix: "",
//             sSearch: "",
//             sEmptyTable: "Nenhum registro encontrado",
//         },
//         dom: '<"top">t<"bottom"p>',
//         initComplete: function () {
//             // $("#overlay").css("display", "none");
//         },
//         pagingType: "full_numbers",
//         responsive: false,
//         lengthMenu: [
//             [15, 30, 100, -1],
//             [15, 30, 100, 'All']
//         ],
//         order: [],
//         data: data,
//         columns: [{
//             data: 'id'
//         },
//         {
//             data: 'nome'
//         },
//         {
//             data: null,
//             render: function (data) {
//                 return "<div class='d-flex'><a title='Editar' style='cursor:pointer; width:100%' onClick='editarItem(" + data.id + ")'><i class='bi bi-pencil-fill'></i></a><a title='visualizarLayout' style='cursor:pointer; width:100%' onClick='visualizarLayout(" + data.id + ")'><i class='bi bi-pencil-fill'></i></a><a title='inativarLayout' style='cursor:pointer; width:100%' onClick='inativarLayout(" + data.id + ")'><i class='bi bi-pencil-fill'></i></a></div>";
//             }
//         }
//         ]
//     });
// }