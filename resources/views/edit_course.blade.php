<!-- resources/views/panel/instructor/course/edit_course.blade.php -->

@extends('layouts.app')

@section('content')
<div class="container">
    <h1>{{ $data['title'] }}</h1>
    <form action="{{ route('instructor.courses.update', $data['course']->slug) }}" method="POST">
        @csrf
        <div class="form-group">
            <label for="title">Course Title</label>
            <input type="text" class="form-control" name="title" value="{{ $data['course']->title }}" required>
        </div>
        <div class="form-group">
            <label for="description">Course Description</label>
            <textarea class="form-control" name="description" rows="4">{{ $data['course']->description }}</textarea>
        </div>
        <div class="form-group">
            <label for="category_id">Category</label>
            <select class="form-control" name="category_id">
                <option value="">Select Category</option>
                @foreach ($data['categories'] as $category)
                    <option value="{{ $category->id }}" {{ $category->id == $data['course']->category_id ? 'selected' : '' }}>
                        {{ $category->name }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="form-group">
            <label for="language_id">Language</label>
            <select class="form-control" name="language_id">
                <option value="">Select Language</option>
                @foreach ($data['languages'] as $language)
                    <option value="{{ $language->id }}" {{ $language->id == $data['course']->language_id ? 'selected' : '' }}>
                        {{ $language->name }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="form-group">
            <label for="price">Price</label>
            <input type="number" class="form-control" name="price" value="{{ $data['course']->price }}" required>
        </div>
        <button type="submit" class="btn btn-primary">Update Course</button>
    </form>
</div>
@endsection
