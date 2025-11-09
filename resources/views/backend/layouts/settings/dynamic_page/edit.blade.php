@extends('backend.app')

@section('title', 'Update Dynamic Page')

@section('content')
    <div class="page-body">
        <div class="container-fluid">
            <div class="row">
                <div class="col-lg-12">
                    <div class="card-style mb-4">
                        <div class="card card-body">
                            <form method="POST" action="{{ route('admin.dynamic_page.update', ['id' => $data->id]) }}">
                                @csrf
                                <div class="input-style-1 mt-4">
                                    <label for="page_title">Title:</label>
                                    <input type="text" placeholder="Enter Title" id="page_title"
                                        class="form-control @error('page_title') is-invalid @enderror" name="page_title"
                                        value="{{ old('page_title', $data->page_title) }}" />
                                    @error('page_title')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>

                                <div class="input-style-1 mt-4">
                                    <label for="page_content">Content:</label>
                                    <textarea placeholder="Type here..." id="page_content" name="page_content"
                                        class="form-control @error('page_content') is-invalid @enderror">
                                    {{ old('page_content', $data->page_content) }}
                                </textarea>
                                    @error('page_content')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>

                                <div class="col-12 mt-4">
                                    <button type="submit" class="btn btn-primary">Submit</button>
                                    <a href="{{ route('admin.dynamic_page.index') }}" class="btn btn-danger me-2">Cancel</a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('script')
    <script>
        ClassicEditor
            .create(document.querySelector('#page_content'))
            .catch(error => {
                console.error(error);
            });
    </script>
@endpush
