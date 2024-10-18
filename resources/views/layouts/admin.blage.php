<!-- resources/views/panel/instructor/dashboard.blade.php -->

@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Instructor Dashboard</h1>
    <div class="row">
        <div class="col-md-4">
            <a href="{{ route('instructor.courses') }}" class="btn btn-primary btn-block">My Courses</a>
        </div>
        <div class="col-md-4">
            <a href="{{ route('instructor.courses.add') }}" class="btn btn-success btn-block">Add New Course</a>
        </div>
    </div>
</div>
@endsection
