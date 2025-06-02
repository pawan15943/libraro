@extends('layouts.learner')
@section('content')



<style>
    .book-card {
        background-color: white;
        border-radius: 15px;
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        overflow: hidden;
        text-align: center;
        padding: 20px;
        transition: transform 0.3s, box-shadow 0.3s;
    }

    .book-card:hover {
        transform: translateY(-10px);
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.2);
    }


    .book-card h3 {
        margin: 10px 0;
        font-size: 20px;
        color: #333;
    }

    .book-card p {
        color: #666;
        margin-bottom: 10px;
        font-size: 14px;
    }

    .book-card .status {
        padding: 8px 15px;
        border-radius: 20px;
        display: inline-block;
        margin-bottom: 15px;
        font-size: 12px;
        font-weight: bold;
    }

    .status.available {
        background-color: #28a745;
        color: white;
    }

    .status.borrowed {
        background-color: #dc3545;
        color: white;
    }


    .book-card img {
        max-width: 100%;
        border-radius: 10px;
        margin-bottom: 15px;
        width: 96px;
    }
</style>


<div class="row g-4">
    <div class="col-lg-6">
        <input type="text" class="form-control" placeholder="Search by title, author, or ISBN">
    </div>
    <div class="col-lg-2">
        <select class="form-select">
            <option value="">All Categories</option>
            <option value="fiction">Fiction</option>
            <option value="non-fiction">Non-Fiction</option>
            <option value="science">Science</option>
            <option value="history">History</option>
        </select>

    </div>
    <div class="col-lg-2">
        <select class="form-select">
            <option value="">Availability</option>
            <option value="available">Available</option>
            <option value="borrowed">Borrowed</option>
        </select>
    </div>
    <div class="col-lg-2">
        <input type="submit" class="btn btn-primary button">
    </div>


</div>

<h4 class="py-4">Our Library Books</h4>
<div class="row">
    <div class="col-lg-3">
        <div class="book-card">
            <img src="{{url('public/img/01.jpg')}}" alt="Book Cover">
            <h3>Book Title 1</h3>
            <p>Author: Author 1</p>
            <span class="status available">Available</span>
        </div>

    </div>
    <div class="col-lg-3">
        <div class="book-card">
            <img src="{{url('public/img/01.jpg')}}" alt="Book Cover">
            <h3>Book Title 2</h3>
            <p>Author: Author 2</p>
            <span class="status borrowed">Borrowed</span>
        </div>
    </div>
</div>
@endsection