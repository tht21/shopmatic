@extends('layouts.app')

@section('content')
    <div class="header bg-default pt-100" style="height: 70px;"><div class="separator separator-bottom separator-skew zindex-100"></div></div>
    <section class="bg-white pt-3 pb-5">
        <div class="container">
            <h1 class="text-dark font-weight-light text-center font-size-30 mb-4 wow fadeInDown">About Us</h1>
            <div class="row text-center align-items-center">
                <div class="col-8 col-md-4">
                    <img alt="image" class="img-fluid wow fadeIn" src="/images/background/map-1.jpg">
                </div>

                <div class="col-4 col-md-2">
                    <div class="row">
                        <div class="col-12">
                            <img alt="image" class="img-fluid wow fadeIn" data-wow-delay="0.2s" src="/images/background/map-2.jpg">
                        </div>
                    </div>

                    <div class="row mt-4">
                        <div class="col-12">
                            <img alt="image" class="img-fluid wow fadeIn" data-wow-delay="0.4s" src="/images/background/map-3.jpg">
                        </div>
                    </div>
                </div>

                <div class="col-12 col-md-6 col-lg-5 ml-auto pt-5 pt-md-0">
                    <p class="wow fadeIn">CombineSell is a Software as a Service (SaaS) platform that automates & simplifies multichannel e-commerce selling processes by aggregating popular online marketplaces into just a single platform.
                    </p>
                    <p class="wow fadeIn" data-wow-delay="0.2s">
                        With CombineSell, you can now sell everywhere your customers shop. It has never been easier - create and manage listings, reply customer enquiries, synchronise & control inventories, and fulfil orders all from a single interface.
                    </p>
                </div>
            </div>
        </div>
    </section>
    <section class="full-background p-5 bg-default">
        <div class="container">
            <div class="row align-items-center justify-content-center">
                <div class="col-12 col-md-8 mx-auto py-3">
                    <h1 class="text-white text-center font-weight-light wow fadeIn">
                        Everyone can be a seller and it's our goal to make sure everyone can sell <strong>much</strong> more than they were able to.
                    </h1>
                </div>
            </div>
        </div>
    </section>
    <section class="bg-white p-5">
        <div class="container">
            <h1 class="mb-5 text-center wow fadeInDown">Meet The Founding Team</h1>

            <div class="row-50"></div>

            <div class="row">
                <div class="col-sm-4 text-left">
                    <div class="card wow fadeIn">
                        <img alt="image" class="card-img-top" src="/images/team/june.jpg">
                        <div class="card-body">
                            <h3><strong>Loh June Yong</strong></h3>
                            <p class="mb-0">Founder, CEO</p>
                        </div>
                    </div>
                </div>
                <div class="col-sm-4 text-left">
                    <div class="card wow fadeIn" data-wow-delay="0.2s">
                        <img alt="image" class="card-img-top" src="/images/team/amanda.jpg">
                        <div class="card-body">
                            <h3><strong>Amanda Ho</strong></h3>
                            <p class="mb-0">Co-Founder, CMO</p>
                        </div>
                    </div>
                </div>
                <div class="col-sm-4 text-left">
                    <div class="card wow fadeIn" data-wow-delay="0.4s">
                        <img alt="image" class="card-img-top" src="/images/team/gerald.jpg">
                        <div class="card-body">
                            <h3><strong>Gerald Lam</strong></h3>
                            <p class="mb-0">Co-Founder, CTO</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <section class="bg-lightest p-5">
        <div class="container text-center">
            <div class="row">
                <div class="col-md-8 mx-auto">

                    <h1 class="text-center wow fadeInDown">We're Hiring</h1>
                    <p class="wow fadeInUp">You are the reason we are excited to come to work everyday. We are a bunch of diverse people, and you are why we are here together.</p>
                    <a href="https://startupjobs.asia/company/combinesell-my" class="btn btn-primary px-5 mt-3 wow jackInTheBox" data-wow-delay="0.3s">Join Us</a>
                </div>
            </div>
        </div>
    </section>
@endsection