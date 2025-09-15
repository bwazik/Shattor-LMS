@extends('layouts.admin.master')

@section('page-css')

@endsection

@section('title', pageTitle('main.scores'))

@section('content')
    <!-- DataTable with Buttons -->
    <x-datatable
        datatableTitle="{{ trans('main.datatableTitle', ['item' => trans('main.scores')]) }} - {{ $offlineQuiz->name }} - {{ $offlineQuiz->teacher->name }} - {{ $offlineQuiz->grade->name }}"
        otherButton="{{ trans('admin/offlineQuizzes.submit') }}" otherIcon="ri-checkbox-circle-line">
        <th></th>
        <th>#</th>
        <th>{{ trans('main.name') }}</th>
        <th>{{ trans('main.score') }}</th>
        <th>{{ trans('main.description') }}</th>
    </x-datatable>
    <!--/ DataTable with Buttons -->

    <!-- Delete Modal -->
    <x-modal modalType="reset-offline-quiz" modalTitle="{{ trans('admin/quizzes.resetStudentQuiz') }}" id
        action="{{ route('admin.offline-quizzes.resetStudentOfflineQuiz', ['id' => $offlineQuiz->id]) }}"
        submitColor="danger" submitButton="{{ trans('main.submit') }}">
        @include('partials.delete-modal-body')
    </x-modal>
@endsection

@section('page-js')
    <script>
        initializeDataTable('#datatable', "{{ route('admin.offline-quizzes.scores', $offlineQuiz->id) }}", [1, 2, 3,
            4],
            [{
                    data: "",
                    orderable: false,
                    searchable: false
                },
                {
                    data: 'DT_RowIndex',
                    name: 'DT_RowIndex',
                    orderable: false,
                    searchable: false
                },
                {
                    data: 'details',
                    name: 'details'
                },
                {
                    data: 'score',
                    name: 'score',
                    orderable: false,
                    searchable: false
                },
                {
                    data: 'note',
                    name: 'note',
                    orderable: false,
                    searchable: false
                },
            ],
        );

        $(document).ready(function() {
            let dataTable = null;
            const url = "{{ route('admin.offline-quizzes.scores.insert', $offlineQuiz->id) }}";

            $('#other-button').on('click', function() {
                let submitButton = $(this);
                submitButton.prop('disabled', true);

                let data = gatherScoresData();

                if (data.length === 0) {
                    toastr.error('{{ trans('admin/offlineQuizzes.noScoresEntered') }}');
                    setTimeout(function() {
                        submitButton.prop('disabled', false);
                    }, 1500);
                    return;
                }

                let payload = {
                    _token: $('meta[name="csrf-token"]').attr('content'),
                    scores: data
                };

                $.ajax({
                    url: url,
                    type: 'POST',
                    dataType: 'json',
                    contentType: 'application/json',
                    data: JSON.stringify(payload),
                    success: function(response) {
                        if (response.success) {
                            toastr.success(response.success);
                            setTimeout(function() {
                                submitButton.prop('disabled', false);
                            }, 1500);
                            refreshDataTable('#datatable');
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

            function gatherScoresData() {
                let scoresData = [];

                $('#datatable tbody tr').each(function() {
                    let $row = $(this);
                    let studentId = $row.find('.score-input').data('student-id');
                    let score = $row.find('.score-input').val();
                    let note = $row.find('.note-input').val();

                    if (studentId && score) {
                        scoresData.push({
                            student_id: studentId,
                            score: score,
                            note: note || null
                        });
                    }
                });

                return scoresData;
            }
        });

        // Setup reset offline quiz modal
        setupModal({
            buttonId: '#reset-offline-quiz-button',
            modalId: '#reset-offline-quiz-modal',
            fields: {
                id: button => button.data('id'),
                itemToDelete: button => `${button.data('name_ar')} - ${button.data('name_en')}`
            }
        });

        handleDeletionFormSubmit('#reset-offline-quiz-form', '#reset-offline-quiz-modal', '#datatable')
    </script>
@endsection
