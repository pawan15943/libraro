@extends('layouts.library')
@section('content')
<meta name="csrf-token" content="{{ csrf_token() }}" />
<!-- Main content -->

<!-- Breadcrumb -->
@if($iscomp==false && !$is_expire)
<div class="row">
    <div class="col-lg-12">
        <div class="steps">
            <ul>

                <li>
                    <a href="{{ ($checkSub) ? '#' : route('subscriptions.choosePlan')  }}">Choose Plan</a>
                </li>
                <li>
                    <a href="{{ ($ispaid) ? '#'  : route('subscriptions.payment') }}">Make Payment</a>
                </li>
                <li>
                    <a href="{{ ($ispaid ) ? route('profile') : '#' }}">Update Profile</a>
                </li>
                <li class="active">
                    <a href="{{ ($checkSub && $ispaid && $isProfile) ? route('library.master') : '#' }}">Configure Library</a>
                </li>
            </ul>

        </div>
    </div>
</div>

<div class="row mb-4">
    <div class="col-lg-12">
        <h2 class="text-center typing-text">You’re all set! Let’s get things ready for you!</h2>
    </div>
</div>
@endif

<div id="success-message" class="alert alert-success" style="display:none;"></div>
<div id="error-message" class="alert alert-danger" style="display:none;"></div>
@if($errors->any())
<div class="alert alert-danger">
    <ul>
        @foreach ($errors->all() as $error)
        <li>{{ $error }}</li>
        @endforeach
    </ul>
</div>
@endif
@if(session('successCount'))
<div class="alert alert-success">
    {{ session('successCount') }} records imported successfully.
</div>
@endif
<!-- Masters -->

@if($iscomp)
<div class="row g-4 mb-4">
    <div class="col-lg-12">
        <p class="info-message">
            <span class="close-btn" onclick="this.parentElement.style.display='none';">&times;</span>
            <b>Important: </b>This is the master console of your library. Everything in your library will function based on the information you enter here, so make sure to add it carefully and accurately.
            <span class="font-weight-blod">यह आपकी लाइब्रेरी का मास्टर कंसोल है। आपकी लाइब्रेरी में सब कुछ इस जानकारी के आधार पर काम करेगा, इसलिए इसे ध्यान से और सही तरीके से जोड़ें।</span>
        </p>
    </div>

    <!-- Add Operating Hours -->
    @can('has-permission','Add Operating Hours')
    <div class="col-lg-4">
        <div class="master-box">
            <div class="d-flex">
                <h4>Add Operating Hours
                    <div class="info-container">
                        <i class="fa-solid fa-circle-info info-icon"></i>
                        <div class="info-card">
                            <h3 class="info-title">Library Operating Hours</h3>
                            <p class="info-details">
                                <span class="d-block">This option allows you to set your library's working hours. Once you've set the library hours, you won’t be able to change them, so make sure they are correct before saving.</span>
                                <span class="d-block mt-2">इस विकल्प का उपयोग करके आप अपनी लाइब्रेरी के कामकाजी घंटे सेट कर सकते हैं। एक बार आपने लाइब्रेरी के घंटे सेट कर दिए, तो आप उन्हें बदल नहीं पाएंगे, इसलिए इसे सही से सेव करने से पहले ध्यान दें।</span>
                            </p>
                        </div>
                    </div>
                </h4>

                @if($hours->isEmpty())
                <i class="fa fa-plus-circle toggle-button"></i>
                @endif

            </div>
            
           
            <div class="master-form mt-3">
                <div class="form-fields">
                    <form id="operating_hour" enctype="multipart/form-data">
                        @csrf
                        <input type="hidden" name="id" value="">
                        <input type="hidden" name="library_id" value="{{getLibraryId()}}">
                        <input type="hidden" name="databasemodel" value="Hour">
                        <div class="row g-3">
                            <div class="col-lg-12">
                                <label for="">Operating Hours <span>*</span></label>
                                <select class="form-select @error('hour') is-invalid @enderror" name="hour" id="hour">
                                    <option value="">Select Hour</option>
                                    <option value="16" {{ old('hour', isset($hour) ? $hour->hour : '') == 16 ? 'selected' : '' }}>16</option>
                                    <option value="14" {{ old('hour', isset($hour) ? $hour->hour : '') == 14 ? 'selected' : '' }}>14</option>
                                    <option value="12" {{ old('hour', isset($hour) ? $hour->hour : '') == 12 ? 'selected' : '' }}>12</option>
                                    @can('has-permission','All Day')
                                    <option value="24" {{ old('hour', isset($hour) ? $hour->hour : '') == 24 ? 'selected' : '' }}>24</option>
                                    @endif
                                </select>
                                @error('hour')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                                @enderror
                            </div>
                            <div class="col-lg-12">
                                <button class="btn btn-primary button" id="saveHourBtn"><i
                                        class="fa fa-plus"></i>
                                    Add Hours</button>
                            </div>
                        </div>
                    </form>
                </div>
                <ul id="hour-list">
                    @foreach($hours as $key => $value)
                    <li>
                        <div class="d-flex">
                            <h4>{{$value->hour}} hours</h4>
                            <ul>
                                <li><a href="#" class="active-deactive" data-id="{{ $value->id }}" data-table="Hour" title="Active/Deactive">
                                        @if($value->deleted_at)
                                        <i class="fas fa-cross"></i>
                                        @else
                                        <i class="fa fa-check"></i>
                                        @endif</a></li>

                                @if($notleaner==0)
                                <li><a href="javascript:void(0)" type="button" class="hour_edit" data-id="{{$value->id}}" data-table="Hour"><i class="fa fa-edit"></i></a></li>

                                @endif

                            </ul>
                        </div>
                    </li>
                    @endforeach

                </ul>
            </div>
                 
            
        </div>
    </div>
    @endcan
    <!-- Add Library Seats -->
    @can('has-permission','Add Library Seats')
    <div class="col-lg-4">
        <div class="master-box">
            <div class="d-flex">
                <h4>Add Library Seats
                    <div class="info-container">
                        <i class="fa-solid fa-circle-info info-icon"></i>
                        <div class="info-card">
                            <h3 class="info-title">Library Seats</h3>
                            <p class="info-details">
                                <span class="d-block">This option lets you add seats to your library. You can add seats for the first time using this feature, so ensure you allocate the correct number of seats.</span>
                                <span class="d-block mt-2">इस विकल्प का उपयोग करके आप अपनी लाइब्रेरी में सीटें जोड़ सकते हैं। पहली बार आप इस विकल्प से सीटें जोड़ सकते हैं, इसलिए सही संख्या में सीटें जोड़ने का ध्यान रखें।</span>
                            </p>
                        </div>
                    </div>
                </h4>
               
                <i class="fa fa-plus-circle toggle-button"></i>
               
            </div>
            <div class="master-form mt-3">
                <div class="form-fields">

                    <form id="library_seat" enctype="multipart/form-data">
                        @csrf
                        <input type="hidden" name="id" value="">
                        <input type="hidden" name="library_id" value="{{getLibraryId()}}">
                         <input type="hidden" name="databasemodel" value="Hour">

                        <div class="row g-3">
                            <div class="col-lg-12">
                                <label for="">Library Seats <span>*</span></label>
                                <input type="text" name="seats" class="form-control @error('seats') is-invalid @enderror" id="" placeholder="Enter Seats No.">
                                @error('seats')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                                @enderror
                            </div>
                            <div class="col-lg-12">
                                <button type="submit" class="btn btn-primary button"><i
                                        class="fa fa-plus"></i>
                                    Add Seats</button>
                            </div>
                        </div>
                    </form>
                </div>
                <ul>
                    @foreach($hours as $key => $value)
                    <li>
                        <div class="d-flex">
                           <h4>{{$value->seats}} Seats</h4>
                            <ul>
                                <li><a href=""><i class="fa fa-check"></i></a></li>
                                @if($notleaner==0)
                                <li><a href="javascript:void(0)" type="button" class="seat_edit" data-id="{{$value->id}}" data-table="Hour"><i class="fa fa-edit"></i></a></li>
                                @endif

                            </ul>
                        </div>
                    </li>
                    @endforeach

                </ul>
            </div>
        </div>
    </div>
    @endcan
    <!-- Add Extend Days -->
    
    @can('has-permission','Add Extend Days')
    <div class="col-lg-4">
        <div class="master-box">
            <div class="d-flex">
                <h4>Extend Days for Expired Seats
                    <div class="info-container">
                        <i class="fa-solid fa-circle-info info-icon"></i>
                        <div class="info-card">
                            <h3 class="info-title"> Extend Days for Expired Seats</h3>
                            <p class="info-details">
                                <span class="d-block">Extend days are extra days that become active after a seat expires. If a seat expires, these days will be automatically added to your plan as a grace period.</span>
                                <span class="d-block mt-2">एक्सटेंड डेज वे अतिरिक्त दिन होते हैं जो सीट एक्सपायर होने के बाद एक्टिव हो जाते हैं। यदि सीट की अवधि समाप्त हो जाती है, तो ये दिन आपके प्लान में स्वचालित रूप से जोड़े जाते हैं।</span>
                            </p>
                        </div>
                    </div>
                </h4>
                @if($is_extendday==false)
                <i class="fa fa-plus-circle toggle-button"></i>
                @endif
            </div>
            <div class="master-form mt-3">
                <div class="form-fields">
                    <form id="extend_hour" class="validateForm" enctype="multipart/form-data">
                        @csrf
                         <input type="hidden" name="id" value="{{getCurrentBranch()}}">
                        <input type="hidden" name="library_id" value="{{getLibraryId()}}">
                        <input type="hidden" name="databasemodel" value="Branch">
                        <div class="row g-3">
                            <div class="col-lg-12">
                                <label for="">Extend Days <span>*</span></label>
                                <input type="text" class="form-control digit-only @error('extend_days') is-invalid @enderror" name="extend_days">
                                @error('extend_days')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                                @enderror
                            </div>
                            <div class="col-lg-12">
                                <button type="submit" class="btn btn-primary button"><i
                                        class="fa fa-plus"></i>
                                    Add Day</button>
                            </div>
                        </div>
                    </form>
                </div>
                <ul id="extend-day-list">
                    @if($is_extendday==true)
                    @foreach($branches as $key => $value)
                    <li>
                        <div class="d-flex">
                            <h4>{{$value->extend_days}} Days</h4>
                            <ul>
                                <li><a href="#">
                                        @if($value->deleted_at)
                                        <i class="fas fa-cross"></i>
                                        @else
                                        <i class="fa fa-check"></i>
                                        @endif</a></li>
                                <li><a href="javascript:void(0)" type="button" class="extend_day_edit" data-id="{{$value->id}}" data-table="Branch"><i class="fa fa-edit"></i></a></li>
                                <li><a href="#" class="active-deactive" data-id="{{ $value->id }}" data-table="Hour" title="Delete"><i class="fa fa-trash"></i></a></li>
                            </ul>
                        </div>
                    </li>
                    @endforeach
                    @endif

                </ul>
            </div>
        </div>
    </div>
    @endcan
    <!-- Add Plan -->
    @can('has-permission','Add Plan')
    <div class="col-lg-4">
        <div class="master-box">
            <div class="d-flex">
                <h4>Add plan
                    <div class="info-container">
                        <i class="fa-solid fa-circle-info info-icon"></i>
                        <div class="info-card">
                            <h3 class="info-title">Adding Plans to Your Library</h3>
                            <p class="info-details">
                                <span class="d-block">This option allows you to add different plans to your library, like Monthly, Quarterly, Half-Yearly, Nine-Month, and Yearly plans.</span>
                                <span class="d-block mt-2">इस विकल्प का उपयोग करके आप अपनी लाइब्रेरी में विभिन्न प्लान्स जोड़ सकते हैं, जैसे कि मंथली, 3-मंथली, 6-मंथली, 9-मंथली और ईयरली प्लान।</span>
                            </p>
                        </div>
                    </div>
                </h4>
                <i class="fa fa-plus-circle toggle-button"></i>
            </div>
            <div class="master-form mt-3">
                <div class="form-fields">
                    <form id="planForm" enctype="multipart/form-data">
                        @csrf
                        @if (isset($plan))
                        @method('PUT')
                        @endif
                        <input type="hidden" name="id" value="">
                        <input type="hidden" name="library_id" value="{{getLibraryId()}}">
                        <input type="hidden" name="databasemodel" value="Plan">
                        <div class="row g-3">
                            <div class="col-lg-12">
                                <label for="">Type <span>*</span></label>
                                 <select class="form-select @error('type') is-invalid @enderror" name="type" id="type">
                                    <option value="">Select Type</option>
                                    <option value="MONTH" {{ old('type', isset($plan) ? $plan->type : '') == 'MONTH' ? 'selected' : '' }}>MONTH</option>
                                    <option value="YEAR" {{ old('type', isset($plan) ? $plan->type : '') == 'YEAR' ? 'selected' : '' }}>YEAR</option>
                                    <option value="DAY" {{ old('type', isset($plan) ? $plan->type : '') == 'DAY' ? 'selected' : '' }}>DAY</option>
                                    <option value="WEEK" {{ old('type', isset($plan) ? $plan->type : '') == 'WEEK' ? 'selected' : '' }}>WEEK</option>
                                   
                                </select>
                                 @error('type')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                                @enderror
                               
                            </div>
                            <div class="col-lg-12">
                                <label for="">Plan <span>*</span></label>
                                 <input type="text" class="form-control digit-only @error('plan_id') is-invalid @enderror" name="plan_id" id="plan_id">
                                 @error('plan_id')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                                @enderror
                            </div>
                            <div class="col-lg-12">
                                <button type="submit" class="btn btn-primary button" id="savePlanBtn"><i class="fa fa-plus"></i>
                                    @if(isset($plan)) Edit Plan @else Add Plan @endif
                                </button>
                            </div>
                        </div>
                    </form>

                </div>
                <ul id="plan-list" class="contents">
                    @foreach($plans as $key => $value)
                    <li>
                        <div class="d-flex">
                            <h4>{{$value->name}}</h4>
                            <ul>
                                <li><a href="#" class="active-deactive" data-id="{{ $value->id }}" data-table="Plan" title="Active/Deactive">
                                        @if($value->deleted_at)
                                        <i class="fas fa-cross"></i>
                                        @else
                                        <i class="fa fa-check"></i>
                                        @endif</a></li>
                                <li><a href="javascript:void(0)" type="button" class="plan_edit" data-id="{{$value->id}}" data-table="Plan"><i class="fa fa-edit"></i></a></li>
                                <li><a href="#" class="delete" data-id="{{ $value->id }}" data-table="Plan" title="Delete"><i class="fa fa-trash"></i></a></li>

                            </ul>
                        </div>
                    </li>
                    @endforeach

                </ul>
            </div>
        </div>
    </div>
    @endcan
    <!-- Add Plan Type -->
    @can('has-permission','Add Master Plan Type')
    <div class="col-lg-4">
        <div class="master-box">
            <div class="d-flex">
                <h4>Add Plan Type
                    <div class="info-container">
                        <i class="fa-solid fa-circle-info info-icon"></i>
                        <div class="info-card">
                            <h3 class="info-title">Plan Type</h3>
                            <p class="info-details">
                                <span class="d-block">This feature lets you add different plan types, such as Full Day, First Half, Second Half, or specific hourly slots (Slot 1, Slot 2, Slot 3, Slot 4).</span>
                                <span class="d-block mt-2">इस फीचर का उपयोग करके आप अपने प्लान में विभिन्न प्रकार जोड़ सकते हैं, जैसे कि फुल डे, फर्स्ट हाफ, सेकंड हाफ, या विशिष्ट घंटों के स्लॉट (स्लॉट 1, स्लॉट 2, स्लॉट 3, स्लॉट 4)।</span>
                            </p>
                        </div>
                    </div>
                </h4>
                <i class="fa fa-plus-circle toggle-button"></i>
            </div>
            <div class="master-form mt-3">
                <div class="form-fields">
                    <form id="planTypeForm" enctype="multipart/form-data">
                        @csrf
                        <input type="hidden" name="id" value="">
                        <input type="hidden" name="library_id" value="{{getLibraryId()}}">
                        <input type="hidden" name="databasemodel" value="PlanType">
                        <div class="row g-3">
                            <div class="col-lg-12">
                                <label for="">Plan Type Name <span>*</span></label>
                                <select class="form-select @error('day_type_id') is-invalid @enderror" name="day_type_id" id="plantype_name">
                                    <option value="">Select Plan Type</option>
                                    @can('has-permission', 'Full Day')
                                    <option value="1" {{ old('day_type_id', isset($planType) ? $planType->day_type_id : '') == 1 ? 'selected' : '' }}>Full Day</option>
                                    @endcan
                                    @can('has-permission', 'First Half')
                                    <option value="2" {{ old('day_type_id', isset($planType) ? $planType->day_type_id : '') == 2 ? 'selected' : '' }}>First Half</option>
                                    @endcan
                                    @can('has-permission', 'Second Half')
                                    <option value="3" {{ old('day_type_id', isset($planType) ? $planType->day_type_id : '') == 3 ? 'selected' : '' }}>Second Half</option>
                                    @endcan
                                    @can('has-permission', 'Hourly Slot 1')
                                    <option value="4" {{ old('day_type_id', isset($planType) ? $planType->day_type_id : '') == 4 ? 'selected' : '' }}>Hourly Slot 1</option>
                                    @endcan
                                    @can('has-permission', 'Hourly Slot 2')
                                    <option value="5" {{ old('day_type_id', isset($planType) ? $planType->day_type_id : '') == 5 ? 'selected' : '' }}>Hourly Slot 2</option>
                                    @endcan
                                    @can('has-permission', 'Hourly Slot 3')
                                    <option value="6" {{ old('day_type_id', isset($planType) ? $planType->day_type_id : '') == 6 ? 'selected' : '' }}>Hourly Slot 3</option>
                                    @endcan
                                    @can('has-permission', 'Hourly Slot 4')
                                    <option value="7" {{ old('day_type_id', isset($planType) ? $planType->day_type_id : '') == 7 ? 'selected' : '' }}>Hourly Slot 4</option>
                                    @endcan
                                    @can('has-permission', 'All Day')
                                    <option value="8" {{ old('day_type_id', isset($planType) ? $planType->day_type_id : '') == 8 ? 'selected' : '' }}>24 hrs</option>
                                    @endcan
                                    @can('has-permission', 'Full Night')
                                    <option value="9" {{ old('day_type_id', isset($planType) ? $planType->day_type_id : '') == 9 ? 'selected' : '' }}>Full Night</option>
                                    @endcan
                                    <option value="0" {{ old('day_type_id', isset($planType) ? $planType->day_type_id : '') == 0 ? 'selected' : '' }}>Custom</option>
                                </select>
                                <div id="custom_plan_type_input" style="display: none; margin-top: 10px;">
                                    <label for="custom_plan_type">Custom Plan Type Name</label>
                                    <input type="text" name="custom_plan_type" id="custom_plan_type" class="form-control char-only" placeholder="Enter custom plan type name">
                                </div>
                                   
                                @error('day_type_id')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                                @enderror
                            </div>
                            <div class="col-lg-6">
                                <label for="">Start Time <span>*</span></label>
                                <input type="text" id="start_time" class="form-control @error('start_time') is-invalid @enderror" name="start_time" value="{{ old('start_time', isset($planType) ? $planType->start_time : '') }}" placeholder="Select start time">
                                @error('start_time')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                                @enderror
                            </div>
                            <div class="col-lg-6">
                                <label for="">End Time <span>*</span></label>
                                <input type="text" id="end_time" class="form-control @error('end_time') is-invalid @enderror" name="end_time" value="{{ old('end_time', isset($planType) ? $planType->end_time : '') }}" placeholder="Select end time">
                                @error('end_time')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                                @enderror
                            </div>
                            <div class="col-lg-6">
                                <label for="">Slot Duration <span>*</span></label>
                                <input type="text" id="slot_hours" class="form-control @error('slot_hours') is-invalid @enderror no-validate" name="slot_hours" readonly value="{{ old('slot_hours', isset($planType) ? $planType->slot_hours : '') }}" placeholder="Slot duration">
                                @error('slot_hours')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                                @enderror
                            </div>
                            <div class="col-lg-6">
                                <label for="seat_color">Select Seat Color <span>*</span></label>
                                <select name="image" id="seat_color" class="form-select no-validate">
                                    <option value="">Select Color</option>
                                    <option value="orange">Orange</option>
                                    <option value="light_orange">Light Orange</option>
                                    <option value="green">Green</option>
                                    <option value="blue">Blue</option>
                                </select>
                            </div>

                            <div class="col-lg-12">
                                <button type="submit" id="savePlanTypeBtn" class="btn btn-primary button"><i
                                        class="fa fa-plus"></i>
                                    Add Plan Type</button>
                            </div>
                        </div>
                    </form>
                </div>
                <ul id="plantype-list" class="contents">
                    @foreach($plantype as $key => $value)
                    <li>
                        <div class="d-flex">
                            <h4>{{$value->name}}</h4>
                            <ul>
                                <li>
                                    <a href="#" class="active-deactive" data-id="{{ $value->id }}" data-table="PlanType" title="Active/Deactive">
                                        @if($value->deleted_at)
                                        <i class="fas fa-cross"></i>
                                        @else
                                        <i class="fa fa-check"></i>
                                        @endif
                                    </a>
                                </li>
                                <li><a href="javascript:void(0)" type="button" class="plantype_edit" data-id="{{$value->id}}" data-table="PlanType"><i class="fa fa-edit"></i></a></li>
                                <li><a href="#" class="delete" data-id="{{ $value->id }}" data-table="PlanType" title="Delete"><i class="fa fa-trash"></i></a></li>

                            </ul>
                        </div>
                    </li>
                    @endforeach
                </ul>

            </div>
        </div>
    </div>
    @endcan
    <!-- Add Plan price -->
    @can('has-permission','Add Master Plan Price')
    <div class="col-lg-4">
        <div class="master-box">
            <div class="d-flex">
                <h4>Add Plan price
                    <div class="info-container">
                        <i class="fa-solid fa-circle-info info-icon"></i>
                        <div class="info-card">
                            <h3 class="info-title">Plan price</h3>
                            <p class="info-details">
                                <span class="d-block">This option allows you to set the price for each plan type. Ensure that the pricing is competitive and matches the services offered.</span>
                                <span class="d-block mt-2">इस विकल्प का उपयोग करके आप हर प्लान टाइप के लिए कीमत सेट कर सकते हैं। सुनिश्चित करें कि कीमत प्रतिस्पर्धी हो और प्रदान की जाने वाली सेवाओं के अनुरूप हो।</span>
                            </p>
                        </div>
                    </div>
                </h4>
                <i class="fa fa-plus-circle toggle-button"></i>
            </div>
            <div class="master-form mt-3">
                <div class="form-fields">
                    <form id="planPriceForm" enctype="multipart/form-data">
                        @csrf
                        <input type="hidden" name="id" value="">
                        <input type="hidden" name="library_id" value="{{getLibraryId()}}">
                        <input type="hidden" name="branch_id" value="{{getCurrentBranch()}}">
                        <input type="hidden" name="databasemodel" value="PlanPrice">
                        <div class="row g-3">
                            <div class="col-lg-12">
                             
                                <label for="">Plan Name <span>*</span></label>
                                <select name="plan_id" id="price_plan_id" class="form-select @error('plan_id') is-invalid @enderror event">
                                    <option value="">Select Plan</option>
                                    @foreach ($plans as $value)
                                    @if($value->plan_id==1)
                                    
                                   
                                    <option value="{{ $value->id }}" {{ isset($planPrice) && $planPrice->plan_id == $value->id ? 'selected' : '' }}>
                                        {{ $value->name }}
                                    </option>
                                     @endif
                                    @endforeach
                                </select>
                                @error('plan_id')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                                @enderror
                            </div>
                            <div class="col-lg-6">
                                <label> Plan Type<sup class="text-danger">*</sup></label>
                                <select name="plan_type_id" id="plan_type_id" class="form-select @error('plan_type_id') is-invalid @enderror event">
                                    <option value="">Select Plan Type</option>
                                    @foreach($plantypes as $planType)
                                    <option value="{{ $planType->id }}" {{ isset($planPrice) && $planPrice->plan_type_id == $planType->id ? 'selected' : '' }}>
                                        {{ $planType->name }}
                                    </option>
                                    @endforeach

                                </select>
                                @error('plan_type_id')
                                <span class="invalid-feedback" role="alert">
                                    {{ $message }}
                                </span>
                                @enderror
                            </div>
                            <div class="col-lg-6">
                                <label for="">Plan Price <span>*</span></label>
                                <input type="text" name="price" class="form-control digit-only @error('price') is-invalid @enderror" id="price" placeholder="Enter Price" value="{{ old('price', isset($planPrice) ? $planPrice->price : '') }}">
                                @error('price')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                                @enderror
                            </div>
                            <div class="col-lg-12">
                                <button type="submit" class="btn btn-primary button"><i
                                        class="fa fa-plus"></i>
                                    Add Plan Price</button>
                            </div>
                        </div>
                    </form>
                </div>
                <ul id="planPrice-list" class="contents">
                    @foreach($planprice as $key => $value)
                    <li>
                        <div class="d-flex">
                            <h4 style="flex:2;">{{ $value->plan->name ?? 'N/A' }}</h4>
                            <h4 style="flex:2;">{{ $value->planType->name ?? 'N/A' }}</h4>
                            <h4>{{$value->price}}</h4>

                            <ul>
                                <li><a href="#" class="active-deactive" data-id="{{ $value->id }}" data-table="PlanPrice" title="Active/Deactive">
                                        @if($value->deleted_at)
                                        <i class="fas fa-cross"></i>
                                        @else
                                        <i class="fa fa-check"></i>
                                        @endif</a></li>
                                <li><a href="javascript:void(0)" type="button" class="planPrice_edit" data-id="{{$value->id}}" data-table="PlanPrice"><i class="fa fa-edit"></i></a></li>
                                <li><a href="#" class="delete" data-id="{{ $value->id }}" data-table="PlanPrice" title="Delete"><i class="fa fa-trash"></i></a></li>

                            </ul>
                        </div>
                    </li>
                    @endforeach

                </ul>
            </div>
        </div>
    </div>
    @endcan
    @can('has-permission','Add Library Seats')
    <div class="col-lg-4">
        <div class="master-box">
            <div class="d-flex">
                <h4>Locker Amount
                    <div class="info-container">
                        <i class="fa-solid fa-circle-info info-icon"></i>
                        <div class="info-card">
                            <h3 class="info-title">Library Locker Amount</h3>
                            <p class="info-details">
                                <span class="d-block">This option lets you add a locker amount to your library. You can add the locker amount for the first time using this feature, so make sure to enter the correct amount.</span>
                                <span class="d-block mt-2">इस विकल्प का उपयोग करके आप अपनी लाइब्रेरी में लॉकर राशि जोड़ सकते हैं। पहली बार आप इस विकल्प से लॉकर राशि जोड़ सकते हैं, इसलिए सही राशि जोड़ना सुनिश्चित करें।</span>
                            </p>
                        </div>
                    </div>
                </h4>
               
                <i class="fa fa-plus-circle toggle-button"></i>
               
            </div>
            <div class="master-form mt-3">
                <div class="form-fields">

                    <form id="library_locker" enctype="multipart/form-data">
                        @csrf
                        
                        <input type="hidden" name="library_id" value="{{getLibraryId()}}">
                         <input type="hidden" name="id" value="{{getCurrentBranch()}}">
                         <input type="hidden" name="databasemodel" value="Branch">

                        <div class="row g-3">
                            <div class="col-lg-12">
                                <label for="">Library Locker Amount <span>*</span></label>
                                <input type="text" name="locker_amount" class="form-control @error('locker_amount') is-invalid @enderror" id="" placeholder="Enter Amt.">
                                @error('locker_amount')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                                @enderror
                            </div>
                            <div class="col-lg-12">
                                <button type="submit" class="btn btn-primary button"><i
                                        class="fa fa-plus"></i>
                                    Add Amount</button>
                            </div>
                        </div>
                    </form>
                </div>
                <ul>
                     @foreach($branches as $key => $value)
                    <li>
                        <div class="d-flex">
                            <h4>{{$value->locker_amount}} amt.</h4>
                            <ul>
                                
                                <li><a href="javascript:void(0)" type="button" class="locker_amount_edit" data-id="{{$value->id}}" data-table="Branch"><i class="fa fa-edit"></i></a></li>
                                
                            </ul>
                        </div>
                    </li>
                    @endforeach

                </ul>
            </div>
        </div>
    </div>
    @endcan
    <!-- Add Expense -->
    @can('has-permission','Add Expense')
    <div class="col-lg-4">
        <div class="master-box">
            <div class="d-flex">
                <h4>Add Expense
                    <div class="info-container">
                        <i class="fa-solid fa-circle-info info-icon"></i>
                        <div class="info-card">
                            <h3 class="info-title">Add Expense</h3>
                            <p class="info-details">
                                <span class="d-block">This option enables you to add different expense types when creating a report, making it easier to track and manage your library’s finances.</span>
                                <span class="d-block mt-2">इस विकल्प का उपयोग करके आप रिपोर्ट बनाते समय विभिन्न खर्च प्रकार जोड़ सकते हैं, जिससे आपकी लाइब्रेरी के वित्त को ट्रैक और प्रबंधित करना आसान हो जाएगा।</span>
                            </p>
                        </div>
                    </div>
                </h4>
                <i class="fa fa-plus-circle toggle-button"></i>
            </div>
            <div class="master-form mt-3">
                <div class="form-fields">

                    <form id="library_expense" enctype="multipart/form-data">
                        @csrf
                        <input type="hidden" name="id" value="">
                        <input type="hidden" name="library_id" value="{{getLibraryId()}}">
                        <input type="hidden" name="databasemodel" value="Expense">
                        <div class="row g-3">
                            <div class="col-lg-12">
                                <label for="class_name"> Expense Name<sup class="text-danger">*</sup> </label>
                                <input type="text" name="name" value="" class="form-control @error('name') is-invalid @enderror">
                                @error('name')
                                <span class="invalid-feedback" role="alert">
                                    {{ $message }}
                                </span>
                                @enderror
                            </div>
                            <div class="col-lg-12">
                                <button type="submit" class="btn btn-primary button"><i
                                        class="fa fa-plus"></i>
                                    Add Expense</button>
                            </div>
                        </div>
                    </form>
                </div>
                <ul class="contents">
                    @foreach($expenses as $key => $value)
                    <li>
                        <div class="d-flex">
                            <h4>{{$value->name}}</h4>

                            <ul>
                                <li><a href="#" class="delete" data-id="{{ $value->id }}" data-table="Expense" title="Active/Deactive">
                                        @if($value->deleted_at)
                                        <i class="fas fa-cross"></i>
                                        @else
                                        <i class="fa fa-check"></i>
                                        @endif</a></li>
                                <li><a href="javascript:void(0)" type="button" class="expense_edit" data-table="Expense" data-id="{{$value->id}}"><i class="fa fa-edit"></i></a></li>
                                <li><a href="#" class="active-deactive" data-id="{{ $value->id }}" data-table="Expense" title="Delete"><i class="fa fa-trash"></i></a></li>
                            </ul>
                        </div>
                    </li>

                    @endforeach

                </ul>
            </div>
        </div>
    </div>
    @endcan
</div>
@else

<div class="row justify-content-center mb-4 mt-4">
    <div class="col-lg-4">
        <div class="import-data" style="min-height: auto;">
            <form action="{{ route('library.master.upload') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="library_id" value=" {{isset($library_id) ? $library_id : ''}}">
                <input type="hidden" name="library_import" value="library_master">
                <div class="row g-4">
                    <div class="col-lg-12">
                        <label for="">Select File</label>
                        <input type="file" class="form-control @error('csv_file') is-invalid @enderror" name="csv_file">
                        @error('csv_file')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                        @enderror

                        
                        
                    </div>
                    <div class="col-lg-12">
                        <button type="submit" class="btn btn-primary button">Import Data</button>
                        <a href="{{ url('public/sample/master.csv') }}" class="sample" download="master.csv">Download Sample library master CSV File</a>
                    </div>
                </div>

            </form>
            <div id="export-progress-container">
                <progress id="export-progress-bar" value="0" max="100" style="width: 100%;"></progress>
                <span id="export-progress-text">Preparing download: 0%</span>
            </div>

        </div>
    </div>
</div>


{{-- Display Invalid Records --}}
<div id="invalid-records-section">
    @if(session('invalidRecords') && count(session('invalidRecords')) > 0)
        <p class="text-danger">Some records could not be imported:</p>
        <div class="table table-responsive">
            <table class="table text-center data-table">
                <thead>
                    <tr>
                        <th>Operating Hour</th>
                        <th>Start Time</th>
                        <th>End Time</th>
                        <th>Seat</th>
                        <th>Full day price</th>
                        <th>Error Message</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach (session('invalidRecords') as $record)
                    <tr>
                        <td>{{ $record['Operating_hour'] ?? 'N/A' }}</td>
                        <td>{{ $record['start_time'] ?? 'N/A' }}</td>
                        <td>{{ $record['end_time'] ?? 'N/A' }}</td>
                        <td>{{ $record['total_seat'] ?? 'N/A' }}</td>
                        <td>{{ $record['fullday_price'] ?? 'N/A' }}</td>
                        <td class="text-danger">{{ $record['error'] ?? 'No error provided' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            <button class="btn btn-danger btn-sm mt-2" id="clearInvalidRecordsButton">Clear Invalid Records</button>
        </div>



        {{-- Trigger CSV Download Automatically --}}
        @if(session('autoExportCsv'))
        <script type="text/javascript">
            let exportProgressBar = document.getElementById('export-progress-bar');
            let exportProgressText = document.getElementById('export-progress-text');
            let exportrecordurl = "{{ Auth::guard('library')->check() ? route('library.export.invalid.records') : route('web.export.invalid.records') }}";

            // Set an interval to update the progress bar every 100 ms
            let progress = 0;
            let interval = setInterval(function() {
                progress += 10;
                exportProgressBar.value = progress;
                exportProgressText.textContent = `Preparing download: ${progress}%`;

                if (progress >= 100) {
                    clearInterval(interval);
                    window.location.href = exportrecordurl;
                }
            }, 100); // 100 ms * 10 = 1 second, change as needed
        </script>
        @endif
    @endif

    <script>
        document.getElementById('clearInvalidRecordsButton').addEventListener('click', function() {
            // Hide the invalid records section
            document.getElementById('invalid-records-section').style.display = 'none';

            // Reset the progress bar
            let exportProgressBar = document.getElementById('export-progress-bar');
            let exportProgressText = document.getElementById('export-progress-text');

            if (exportProgressBar && exportProgressText) {
                exportProgressBar.value = 0; // Reset progress to 0
                exportProgressText.textContent = ""; // Clear text percentage
            }

            let clearSessionRoute = "{{ Auth::guard('library')->check() ? route('library.clear.session') : route('web.clear.session') }}";

            // Send AJAX request to clear session
            fetch(clearSessionRoute, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({})
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        console.log('Session cleared successfully.');
                    } else {
                        console.log('Failed to clear session.');
                    }
                })
                .catch(error => console.error('Error:', error));
        });
    </script>
</div>
@endif


<!-- Button -->
@if($iscomp)
<div class="row justify-content-center mb-4">
    <div class="col-lg-4">
        {{-- @if($seat_button) --}}
        <a href="{{route('seats')}}" type="button" class="btn btn-primary button main">Take me to My Dashboard </a>
        {{-- @endif --}}
    </div>
</div>
@endif
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/malihu-custom-scrollbar-plugin/3.1.5/jquery.mCustomScrollbar.min.css">
<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.0/jquery.min.js"></script>

<script src="https://cdnjs.cloudflare.com/ajax/libs/malihu-custom-scrollbar-plugin/3.1.5/jquery.mCustomScrollbar.concat.min.js"></script>

<script>
    (function($) {
        $(window).on("load", function() {
            $(".contents").mCustomScrollbar({
                theme: "dark",
                scrollInertia: 300,
                axis: "y",
                autoHideScrollbar: false, // Keeps
            });
        });
    })(jQuery);
</script>
<script>
    $(document).ready(function() {
        function toggleCustomInput() {
            if ($('#plantype_name').val() == '0') {
                $('#custom_plan_type_input').show();
            } else {
                $('#custom_plan_type_input').hide();
            }
        }

        toggleCustomInput(); // Call on page load
        $('#plantype_name').change(toggleCustomInput);
    });
</script>

<!-- /.content -->
@include('master.script')
@endsection