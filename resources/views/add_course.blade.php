<!-- resources/views/panel/instructor/course/add_course.blade.php -->

@extends('layouts.app')

@section('content')
<div class="container">
    <h1>{{ $data['title'] }}</h1>
    <form action="{{ route('instructor.courses.store') }}" method="POST">
        @csrf
        <div class="form-group">
            <label for="title">Course Title</label>
            <input type="text" class="form-control" name="title" required>
        </div>
        <div class="form-group">
            <label for="description">Course Description</label>
            <textarea class="form-control" name="description" rows="4"></textarea>
        </div>
        <div class="form-group">
            <label for="category_id">Category</label>
            <select class="form-control" name="category_id">
                <option value="">Select Category</option>
                @foreach ($data['categories'] as $category)
                    <option value="{{ $category->id }}">{{ $category->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="form-group">
            <label for="language_id">Language</label>
            <select class="form-control" name="language_id">
                <option value="">Select Language</option>
                @foreach ($data['languages'] as $language)
                    <option value="{{ $language->id }}">{{ $language->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="form-group">
            <label for="price">Price</label>
            <input type="number" class="form-control" name="price" required>
        </div>
        <button type="submit" class="btn btn-primary">Create Course</button>
    </form>
</div>
@endsection
