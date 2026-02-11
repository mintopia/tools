@extends('layouts.app')

@section('content')
    @livewire('flight-calendar-view', ['calendar' => $calendar])
@endsection

