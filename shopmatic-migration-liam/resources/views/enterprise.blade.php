@extends('layouts.app')

@section('content')
    <div class="header bg-gradient-primary"><div class="separator separator-bottom separator-skew zindex-100"></div></div>
    <section>
        <div class="container-fluid py-5 header-enterprise">
            <div class="container py-5">
                <div class="row">
                    <div class="col-12 py-4 text-center">
                        <h1 class="font-weight-light font-size-40 text-white wow fadeInDown mb-0">Expand Your Business</h1>
                        <h2 class="font-weight-light text-white wow fadeInUp mt-0 mb-5">Let us grow your digital presence</h2>

                        <a class="btn btn-primary wow jackInTheBox" href="{{ route('contact.index') }}">Contact Us</a>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <section class="py-5 bg-white">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-10 col-sm-6 col-md-5 col-lg-4 m-auto pb-5 pb-md-0">
                    <img alt="image" class="img-fluid rounded-0 wow rotateInDownLeft" src="/images/mail.svg">
                </div>

                <div class="col-12 ml-md-auto col-md-7 col-lg-6 pb-5 pb-md-0">
                    <h1 class="wow fadeInDown">Sit back & Relax</h1>
                    <p class="wow fadeInUp">We provide end to end services for companies and will help get everything started as well as continue to manage it.</p>
                </div>
            </div>
        </div>
    </section>

    <section class="py-5 bg-lightest">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-12 col-md-7 ml-md-auto">
                    <h1 class="wow fadeInDown">Product Listing</h1>
                    <p class="mt-4 wow fadeInUp" data-wow-delay="0.3s">Let us handle the creating of your product listings. Our experienced team will help you create the graphics you need and even provide copy writing for your products!</p>
                </div>

                <div class="col-10 col-sm-6 col-md-5 m-auto pt-5 pt-md-0">
                    <img alt="image" class="img-fluid rounded-0 wow fadeIn" src="/images/task.svg">
                </div>
            </div>
        </div>
    </section>
    <section class="py-5 bg-cyan">
        <div class="container text-white">
            <div class="row align-items-center pb-xl-5">
                <div class="col-12 col-md-7">
                    <h1 class="text-white mb-3 wow fadeInDown">Campaign Management</h1>
                    <p class="wow fadeIn">Our team helps with planning as well as optimizing campaigns for all your products on all the different platforms!</p>
                </div>
                <div class="col-12 col-sm-6 col-md-5 m-sm-auto mr-md-0 ml-md-auto pt-4 pt-md-0">
                    <img alt="image" class="img-fluid wow fadeIn" src="/images/analytics.svg">
                </div>
            </div>

            <div class="row">
                <div class="col-12 col-sm-6 wow fadeInDown" data-wow-delay="0.3s">
                    <h3 class="text-white"><strong>A|B Testing</strong></h3>
                    <p>We derive analytics from A|B marketing campaign testing as well as from historical campaign data.</p>
                </div>

                <div class="col-12 col-sm-6 pt-3 pt-sm-0 wow fadeInDown" data-wow-delay="0.6s">
                    <h3 class="text-white"><strong>Optimization</strong></h3>
                    <p>Our system allows us to find the best campaign to ensure every dollar spent is spent effectively.</p>
                </div>
            </div>
        </div>
    </section>
    <section class="bg-lightest py-5">
        <div class="container">

            <div class="row align-items-center">

                <div class="col-10 col-sm-6 col-md-5 m-auto pt-5 pt-md-0">
                    <img alt="image" class="img-fluid rounded-0 wow fadeIn" src="/images/enquiry.svg">
                </div>
                <div class="col-12 col-md-7 ml-md-auto pl-5">
                    <h1 class="wow fadeInDown">Customer Service</h1>
                    <p class="mt-4 wow fadeInUp" data-wow-delay="0.3s">Gone are the days where you have to worry about building your customer support team. Our diverse and expansive team will do it for you!</p>
                </div>
            </div>
        </div>
    </section>
    <section class="py-5 bg-white">
        <div class="container pb-5">
            <div class="row align-items-center">
                <div class="col-12 col-md-7">
                    <h1 class="wow fadeInDown">Order Management</h1>
                    <p class="mt-3 wow fadeInUp">Fulfill and manage all your orders from a single place. No longer will you lose track of your orders.</p>

                    <div class="row pt-4 pt-xl-5">
                        <div class="col-12 col-md-5 wow fadeIn" data-wow-delay="0.3s">
                            <h4><strong>Fulfilment</strong></h4>
                            <p>Fulfill orders using your own or even our logistics partners</p>
                        </div>
                        <div class="col-12 col-md-5 m-auto pt-3 pt-md-0 wow fadeIn" data-wow-delay="0.6s">
                            <h4><strong>Bulk Processing</strong></h4>
                            <p>Tired of doing everything one by one? Process all orders together!</p>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-md-5 m-auto mr-lg-0 ml-lg-auto pt-5 pt-lg-0">
                    <img alt="image" class="img-fluid wow fadeIn" data-wow-duration="1.5s" src="/images/scrum.svg">
                </div>
            </div>
        </div>
    </section>

    <section class="p-5 full-background" style="background-image: url('images/background/orange.svg');" >
        <div class="container align-items-center justify-content-center d-flex">
            <div class="row justify-content-center text-center">
                <div class="col-12">
                    <h1 class="text-white wow fadeInDown">We have increased revenues for companies by over ten-fold! <br />Let us see how we can help you.</h1>
                    <p class="mt-5"><a href="{{ route('contact.index') }}" class="btn btn-outline-white wow fadeInUp">Contact Us!</a></p>
                </div>
            </div>
        </div>
    </section>
@endsection
