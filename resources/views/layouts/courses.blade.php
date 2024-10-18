<!-- resources/views/panel/instructor/course/my_courses.blade.php -->

@extends('layouts.app')

@section('content')
<div class="container">
    <h1>{{ $data['title'] }}</h1>
    <a href="{{ route('instructor.courses.add') }}" class="btn btn-primary mb-3">Add New Course</a>
    @if ($data['courses']->count())
    <table class="table table-striped">
        <thead>
            <tr>
                <th>Title</th>
                <th>Category</th>
                <th>Language</th>
                <th>Price</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($data['courses'] as $course)
            <tr>
                <td>{{ $course->title }}</td>
                <td>{{ $course->category ? $course->category->name : 'Uncategorized' }}</td>
                <td>{{ $course->language ? $course->language->name : 'No Language' }}</td>
                <td>${{ $course->price }}</td>
                <td>
                    <a href="{{ route('instructor.courses.edit', $course->slug) }}" class="btn btn-warning">Edit</a>
                    <form action="{{ route('instructor.courses.delete', $course->id) }}" method="POST" style="display:inline-block;">
                        @csrf
                        @method('DELETE')
                        <button class="btn btn-danger" type="submit">Delete</button>
                    </form>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    {{ $data['courses']->links() }} <!-- Pagination Links -->
    @else
        <p>No courses available. <a href="{{ route('instructor.courses.add') }}">Add a course</a></p>
    @endif
</div>
@endsection
