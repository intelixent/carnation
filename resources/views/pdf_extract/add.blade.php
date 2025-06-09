@extends('layouts.app')

@section('pagetitle', $page_title)
@section('content')
<style>
    .card .card-header {
        background-color: #fdb71473 !important;
    }

    #extractionResults {
        margin-top: 20px;
        display: none;
    }

    #resultsTable {
        width: 100%;
        border-collapse: collapse;
    }

    #resultsTable th,
    #resultsTable td {
        border: 1px solid #ddd;
        padding: 8px;
    }

    #resultsTable th {
        background-color: #f2f2f2;
        text-align: left;
    }

    .error {
        color: red;
    }
</style>
<div class="container-fluid">
    <!-- BreadCrumbs -->
    <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
        <div class="my-auto">
            <h5 class="page-title fs-21 mb-1">@yield('pagetitle')</h5>
            <nav>
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="javascript:void(0);">{{$page_main_title}}</a></li>
                    <li class="breadcrumb-item active" aria-current="page">{{$page_title}}</li>
                </ol>
            </nav>
        </div>
    </div>
    <!-- BreadCrumbs -->

    <!-- row -->
    <div class="row">
        <div class="col-xl-12">
            <div class="card">
                <div class="card-header bg-primary d-flex justify-content-between align-items-center">
                    <h5 class="card-title text-dark mb-0">Pdf Extract Add</h5>
                </div>
                <div class="card-body">
                    <form id="pdfExtractAddForm" enctype="multipart/form-data">
                        <div class="row mb-3">
                            <div class="col-sm-6">
                                <label for="vendor_id" class="form-label">Select Vendor <span class="text-danger">*</span></label>
                                <select name="vendor_id" id="vendor_id" class="form-control select2" required>
                                    <option value="">Select Vendor</option>
                                    @foreach($vendors as $vendor)
                                    <option value="{{ $vendor->id }}" {{ request('vendor_id') == $vendor->name ? 'selected' : '' }}>
                                        {{ $vendor->name }}
                                    </option>
                                    @endforeach
                                </select>
                            </div>
                            <input type="hidden" name="custom_field_no" id="custom_field_no" value="">
                            <input type="hidden" name="extraction_no" id="extraction_no" value="">
                            <div class="col-sm-6">
                                <label for="pdf_file" class="form-label">Pdf File</label>
                                <input class="form-control" type="file" id="pdf_file" name="pdf_file">
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-sm-12">
                                <button class="btn btn-primary float-end" type="submit" id="submit_btn" name="submit_btn">Submit</button>
                            </div>
                        </div>
                    </form>

                    <!-- Results section -->
                    <div class="resultsContainer">

                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- row closed -->
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/jquery-validation@1.19.5/dist/jquery.validate.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/jquery-validation@1.19.5/dist/additional-methods.min.js"></script>
<script type="text/javascript">
    $(document).ready(function() {
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        $('.select2').select2();

        $('#vendor_id').on('change', function() {
            var vendor_id = $(this).val();

            if (vendor_id) {
                $.ajax({
                    url: "{{ route('get_vendor_custom_field') }}",
                    method: 'POST',
                    data: {
                        'vendor_id': vendor_id,
                        '_token': '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        if (response.success) {
                            $('#custom_field_no').val(response.custom_field_no);
                            $('#extraction_no').val(response.extraction_no);
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Error fetching vendor custom field:', error);
                    }
                });
            } else {
                $('#custom_field_no').val('');
                $('#extraction_no').val('');
            }
        });

        $('#pdfExtractAddForm').validate({
            rules: {
                extraction_no: 'required',
                pdf_file: {
                    required: true,
                    extension: "pdf"
                }
            },
            messages: {
                extraction_no: {
                    required: "Please select a Vendor"
                },
                pdf_file: {
                    required: "Please select a PDF file",
                    extension: "Only PDF files are allowed"
                }
            },
            errorClass: 'error',
            errorElement: 'span',
            errorPlacement: function(error, element) {
                if (element.hasClass("select2-hidden-accessible")) {
                    error.insertAfter(element.next('.select2-container'));
                } else {
                    error.insertAfter(element);
                }
            },
            highlight: function(element) {
                if ($(element).hasClass("select2-hidden-accessible")) {
                    $(element).next('.select2-container').addClass('error-border');
                } else {
                    $(element).addClass('error-border');
                }
            },
            unhighlight: function(element) {
                if ($(element).hasClass("select2-hidden-accessible")) {
                    $(element).next('.select2-container').removeClass('error-border');
                } else {
                    $(element).removeClass('error-border');
                }
            },
            submitHandler: function(form) {
                // Convert PDF to base64
                const pdfFile = $('#pdf_file')[0].files[0];
                const reader = new FileReader();

                reader.onload = function(e) {
                    const base64Pdf = e.target.result.split(',')[1];
                    const extractionNo = $('#extraction_no').val();

                    Swal.fire({
                        title: 'Loading...',
                        html: 'Extracting the pdf details',
                        allowOutsideClick: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });

                    $.ajax({
                        // url: "http://localhost:8000/process",
                        url: '{{ route("pdf_process") }}',
                        type: "POST",
                        data: {
                            'extraction_no': extractionNo,
                            'pdf_base64': base64Pdf
                        },
                        //contentType: "application/json",
                        dataType: "json",
                        success: function(response) {
                            Swal.close();

                            if (response.status == true) {
                                $('.resultsContainer').html(response.html);
                                document.getElementById("verifyCheck").addEventListener("change", function() {
                                    document.getElementById("saveButton").disabled = !this.checked;
                                });

                                $.toast({
                                    heading: 'Success',
                                    text: 'PDF extracted successfully',
                                    position: 'top-center',
                                    bgColor: '#000',
                                    textColor: 'white',
                                    hideAfter: 3000,
                                    stack: 6
                                });
                            } else {
                                $.toast({
                                    heading: 'Error',
                                    text: response.message || 'Failed to extract PDF data',
                                    position: 'top-center',
                                    bgColor: '#FF0000',
                                    textColor: 'white',
                                    hideAfter: 3000,
                                    stack: 6
                                });
                            }
                        },
                        error: function(xhr) {
                            Swal.close();
                            $.toast({
                                heading: 'Error',
                                text: 'An error occurred while processing the PDF.',
                                position: 'top-center',
                                bgColor: '#FF0000',
                                textColor: 'white',
                                hideAfter: 3000,
                                stack: 6
                            });
                        }
                    });
                };

                reader.readAsDataURL(pdfFile);
                return false;
            }
        });
    });

    $(document).on('click', '#saveButton', function() {
        var vendor_name = $("#vendor_name").val();
        var custom_field_no = $("#custom_field_no").val();
        var hsn_code = $("#hsn_code").val();
        var vendor_id = $('#vendor_id').val();

        if (custom_field_no === "1" && (!hsn_code || hsn_code.trim() === "")) {
            Swal.fire({
                icon: 'warning',
                title: 'HSN Code Required',
                text: 'Please enter HSN Code before proceeding.',
                confirmButtonText: 'OK'
            });
            return false;
        }

        var po_data = $(".po_data").val();
        var po_details = $(".po_details").val();
        var article_details = $(".article_details").val();
        var po_items = $(".po_items").val();
        var po_unit_price = $(".po_unit_price").val();
        var po_qty = $(".po_qty").val();

        $.ajax({
            url: "{{route('check_po_exists')}}",
            method: 'POST',
            data: {
                'po_data': po_data,
                'po_details': po_details,
                'vendor_id': vendor_id,
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                if (response.exists) {
                    Swal.fire({
                        title: 'PO Already Exists!',
                        text: `Purchase Order "${response.po_num}" already exists in the database. Do you want to proceed anyway?`,
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#3085d6',
                        cancelButtonColor: '#d33',
                        confirmButtonText: 'Yes, Save Anyway!',
                        cancelButtonText: 'Cancel'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            proceedWithSave();
                        }
                    });
                } else {
                    proceedWithSave();
                }
            },
            error: function(xhr, status, error) {
                console.error('Error checking PO existence:', error);
                proceedWithSave();
            }
        });

        function proceedWithSave() {
            Swal.fire({
                title: 'Loading...',
                html: 'Please wait while we store the details',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            $.ajax({
                url: "{{route('pdf_extract_store')}}",
                method: 'POST',
                data: {
                    'po_data': po_data,
                    'po_details': po_details,
                    'article_details': article_details,
                    'po_items': po_items,
                    'po_unit_price': po_unit_price,
                    'po_qty': po_qty,
                    'vendor_name': vendor_name,
                    'vendor_id': vendor_id,
                    'hsn_code': hsn_code,
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    Swal.close();
                    $.toast({
                        heading: 'Success',
                        text: response.message,
                        position: 'top-center',
                        bgColor: '#000',
                        textColor: 'white',
                        hideAfter: 3000,
                        stack: 6
                    });
                    setTimeout(function() {
                        location.reload();
                    }, 3000);
                },
                error: function(xhr, status, error) {
                    Swal.close();
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Failed to load details. Please try again.'
                    });
                    console.error(error);
                }
            });
        }
    });
</script>
@endpush