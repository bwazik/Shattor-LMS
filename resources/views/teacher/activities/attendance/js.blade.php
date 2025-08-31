<script src="https://cdn.jsdelivr.net/npm/jsqr@1.4.0/dist/jsQR.min.js"></script>

<script>
    $(document).ready(function() {
        let dataTable = null;
        const form = $('#students-form');
        const formId = '#students-form';
        const url = form.attr('action');

        form.on('submit', function(e) {
            e.preventDefault();
            submitButton = $(this).find('button[type="submit"]');
            submitButton.prop('disabled', true);

            const fields = ['grade_id', 'group_id', 'lesson_id'];
            // Clear previous error states
            $.each(fields, function(_, field) {
                $(formId + ' #' + field).removeClass('is-invalid');
                $(formId + ' #' + field + '_error').text('').addClass('d-none').removeClass(
                    'd-block');
            });

            const formData = {
                grade_id: $(formId + ' #grade_id').val(),
                group_id: $(formId + ' #group_id').val(),
                lesson_id: $(formId + ' #lesson_id').val(),
            };

            if (!formData.grade_id || !formData.group_id || !formData.lesson_id) {
                toastr.error('Please select a grade, group, and lesson');
                setTimeout(function() {
                    submitButton.prop('disabled', false);
                }, 1500);
                return;
            }

            if (dataTable) {
                dataTable.destroy();
            }

            datatable = initializePostDataTable('#datatable', url, [2, 3, 4],
                [{
                        data: "",
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'id',
                        name: 'id'
                    },
                    {
                        data: 'name',
                        name: 'name',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'type',
                        name: 'type',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'note',
                        name: 'note',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'actions',
                        name: 'actions',
                        orderable: false,
                        searchable: false
                    }
                ], {
                    grade_id: $('#students-form #grade_id').val(),
                    group_id: $('#students-form #group_id').val(),
                    lesson_id: $('#students-form #lesson_id').val(),
                },
                '#students-form'
            );
        });
    });

    function gatherAttendanceData() {
        let attendanceData = [];

        $('#datatable tbody tr').each(function() {
            let $row = $(this);

            let studentId = $row.find('.status-container').data('student-id');
            let note = $row.find('.note-input').val();
            let activeButton = $row.find('.status-container .status-btn.active');
            let status = activeButton.length > 0 ? activeButton.data('status') : null;

            if (studentId && status !== null) {
                attendanceData.push({
                    student_id: studentId,
                    status: status,
                    note: note || null
                });
            }
        });

        return attendanceData;
    }

    $(document).ready(function() {
        $('#other-button').on('click', function() {
            let submitButton = $(this);
            submitButton.prop('disabled', true);

            let form = $('#students-form');
            let data = gatherAttendanceData();

            if (data.length === 0) {
                toastr.error('{{ trans('admin/attendance.noStudentsSelected') }}');
                setTimeout(function() {
                    submitButton.prop('disabled', false);
                }, 1500);
                return;
            }

            let payload = {
                _token: $('meta[name="csrf-token"]').attr('content'),
                grade_id: form.find('#grade_id').val(),
                group_id: form.find('#group_id').val(),
                lesson_id: form.find('#lesson_id').val(),
                attendance: data
            };

            $.ajax({
                url: "{{ route('teacher.attendance.insert') }}",
                type: 'POST',
                dataType: "json",
                contentType: "application/json",
                data: JSON.stringify(payload),
                success: function(response) {
                    if (response.success) {
                        toastr.success(response.success)
                        setTimeout(function() {
                            submitButton.prop('disabled', false);
                        }, 1500);
                        refreshDataTable("#datatable");
                    } else {
                        toastr.error(response.error || errorMessage);
                        setTimeout(function() {
                            submitButton.prop('disabled', false);
                        }, 1500);
                    }
                },
                error: function(xhr, status, error) {
                    if (xhr.status === 429) {
                        toastr.error(tooManyRequestsMessage);
                    } else if (xhr.responseJSON) {
                        if (xhr.responseJSON.errors) {
                            $.each(xhr.responseJSON.errors, function(key, val) {
                                toastr.error(val[0]);
                            });
                        } else if (xhr.responseJSON.error) {
                            toastr.error(xhr.responseJSON.error);
                        } else {
                            toastr.error(errorMessage);
                        }
                    } else {
                        toastr.error(errorMessage);
                    }

                    setTimeout(function() {
                        submitButton.prop('disabled', false);
                    }, 1500);
                },
            });
        });
    });

    $(document).on('click', '.status-btn', function() {
        let $button = $(this);
        let $statusContainer = $button.closest('.status-container');

        $statusContainer.find('.status-btn').each(function() {
            $(this).removeClass('active')
                .css('color', function() {
                    return $(this).css('border-color');
                })
                .css('opacity', '0.5');
        });

        $button.addClass('active')
            .css('color', 'white')
            .css('opacity', '1');

        // checkAllStatusSelected();
    });

    // function checkDateAndUpdateButton() {
    //     let selectedDate = document.getElementById('date').value;
    //     let today = "{{ now()->toDateString() }}";
    //     let submitButton = document.getElementById('other-button');

    //     submitButton.disabled = selectedDate !== today;
    // }
    // document.addEventListener('DOMContentLoaded', function() {
    //     checkDateAndUpdateButton();
    // });
    // document.getElementById('date').addEventListener('change', function() {
    //     checkDateAndUpdateButton();
    // });

    $('#mark-all').on('click', function() {
        let allMarked = true;

        $('.status-container').each(function() {
            const activeStatus = $(this).find('.status-btn.active').data('status');
            if (activeStatus !== 1) {
                allMarked = false;
                return false;
            }
        });

        if (allMarked) {
            $('.status-btn').removeClass('active')
                .css('color', function() {
                    return $(this).css('border-color');
                })
                .css('opacity', '0.5');
        } else {
            $('.status-container').each(function() {
                const $presentBtn = $(this).find('.status-btn[data-status="1"]');
                $presentBtn.click();
            });
        }
    });


    // function checkAllStatusSelected() {
    //     let allSelected = true;
    //     $('.status-container').each(function() {
    //         if (!$(this).find('.active').length) {
    //             allSelected = false;
    //         }
    //     });

    //     $('#other-button').prop('disabled', !allSelected);
    // }

    $(document).ready(function() {
        const video = document.getElementById('qr-video');
        const startScanner = document.getElementById('start-scanner');
        const form = $('#students-form');
        const scanUrl = "{{ route('teacher.attendance.scan') }}";
        let isScanning = true;
        let lastScanTime = 0;
        let lastInvalidScanTime = 0;

        const successSound = new Audio('{{ asset('assets/sounds/attendance-sound.mp3') }}');

        startScanner.addEventListener('click', function() {
            const gradeId = form.find('#grade_id').val();
            const groupId = form.find('#group_id').val();
            const lessonId = form.find('#lesson_id').val();

            if (!gradeId || !groupId || !lessonId) {
                toastr.error('Please select a grade, group, and lesson');
                return;
            }

            if (this.dataset.scanning === 'true') {
                // Stop scanning
                video.srcObject?.getTracks().forEach(track => track.stop());
                video.classList.remove('active');
                this.textContent = '{{ trans('admin/attendance.startScanner') }}';
                this.classList.replace('btn-danger', 'btn-success');
                this.dataset.scanning = 'false';
                isScanning = false;
                return;
            }

            // Start scanning
            navigator.mediaDevices.getUserMedia({
                    video: {
                        facingMode: 'environment'
                    }
                })
                .then(stream => {
                    video.srcObject = stream;
                    video.classList.add('active');
                    video.play();
                    this.textContent = '{{ trans('admin/attendance.stopScanner') }}';
                    this.classList.replace('btn-success', 'btn-danger');
                    this.dataset.scanning = 'true';
                    isScanning = true;

                    const canvas = document.createElement('canvas');
                    const context = canvas.getContext('2d');

                    function scan() {
                        if (!isScanning || video.readyState !== video.HAVE_ENOUGH_DATA ||
                            startScanner.dataset.scanning !== 'true') {
                            requestAnimationFrame(scan);
                            return;
                        }

                        canvas.height = video.videoHeight;
                        canvas.width = video.videoWidth;
                        context.drawImage(video, 0, 0, canvas.width, canvas.height);
                        const imageData = context.getImageData(0, 0, canvas.width, canvas.height);
                        const code = jsQR(imageData.data, imageData.width, imageData.height);

                        if (code && code.data) {
                            const currentTime = Date.now();
                            if (currentTime - lastScanTime < 2000) {
                                requestAnimationFrame(scan);
                                return;
                            }

                            try {
                                const url = new URL(code.data);
                                const path = url.pathname.replace(/^\/+/, '').split('/');
                                if ((path[0] === 'ar' && path[1] === 'student' && path[2] ===
                                        'account' && path[3] === 'qr' && path[4]) ||
                                    (path[0] === 'student' && path[1] === 'account' && path[2] ===
                                        'qr' && path[3])) {
                                    const uuid = path[0] === 'ar' ? path[4] : path[3];
                                    isScanning = false;
                                    $.ajax({
                                        url: scanUrl,
                                        type: 'POST',
                                        data: {
                                            _token: $('meta[name="csrf-token"]').attr(
                                                'content'),
                                            uuid: uuid,
                                            grade_id: gradeId,
                                            group_id: groupId,
                                            lesson_id: lessonId
                                        },
                                        success: function(response) {
                                            if (response.success) {
                                                console.log(response);

                                                toastr.success(response.success)
                                                successSound.play().catch(function(error) {
                                                    console.warn('Audio playback failed:', error);
                                                });
                                            } else {
                                                toastr.error(response.error ||
                                                    errorMessage);
                                            }
                                        },
                                        error: function(xhr, status, error) {
                                            if (xhr.status === 429) {
                                                toastr.error(tooManyRequestsMessage);
                                            } else if (xhr.responseJSON) {
                                                if (xhr.responseJSON.errors) {
                                                    $.each(xhr.responseJSON.errors,
                                                        function(key, val) {
                                                            toastr.error(val[0]);
                                                        });
                                                } else if (xhr.responseJSON.error) {
                                                    toastr.error(xhr.responseJSON
                                                    .error);
                                                } else {
                                                    toastr.error(errorMessage);
                                                }
                                            } else {
                                                toastr.error(errorMessage);
                                            }
                                        },
                                        complete: function() {
                                            lastScanTime = Date
                                                .now();
                                            isScanning = true;
                                        }
                                    });
                                } else {
                                    if (currentTime - lastInvalidScanTime >= 2000) {
                                        toastr.error('{{ trans('admin/attendance.invalidQR') }}');
                                        lastInvalidScanTime = currentTime;
                                    }
                                    isScanning = true;
                                }
                            } catch (e) {
                                if (currentTime - lastInvalidScanTime >= 2000) {
                                    toastr.error('{{ trans('admin/attendance.invalidQR') }}');
                                    lastInvalidScanTime = currentTime;
                                }
                                isScanning = true;
                            }
                        }
                        requestAnimationFrame(scan);
                    }
                    requestAnimationFrame(scan);
                })
                .catch(err => {
                    toastr.error('{{ trans('admin/attendance.cameraError') }}');
                });
        });
    });
</script>
