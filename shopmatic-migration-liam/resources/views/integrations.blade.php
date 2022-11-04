@extends('layouts.app')

@section('content')
    <div class="header bg-default pt-100" style="height: 70px;">
        <div class="separator separator-bottom separator-skew zindex-100"></div>
    </div>
    <section class="full-background py-5 bg-white">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-12 col-md-6 col-lg-5">
                    <h1 class="wow fadeInDown">Integrations</h1>
                    <p class="wow fadeIn d-block" data-wow-delay="0.3s">We're constantly adding more integrations
                        and partners! If there's a integration you think we should add, <a
                                href="{{ route('contact.index') }}">contact us!</a> about it.</p>
                </div>
                <div class="col-10 col-sm-6 m-auto col-md-4 pt-4 pt-md-0">
                    <i class="fa fa-link fa-10x wow jackInTheBox"></i>
                </div>
            </div>
        </div>
    </section>
    <section class="full-background bg-white py-5" style="background-image: url(/images/background/8.svg)">
        <div class="container py-5">
            <h1 class="font-weight-light">Marketplaces / Storefronts</h1>
            <hr/>
            <div class="row">
                <div class="col-md-12"><h4 class="font-weight-light">Global</h4>
                    <div class="row pt-3">
                        <div class="col-6 col-md-3 mb-4">
                            <div class="integration-box d-flex wow fadeInDown" data-wow-delay="">
                                <img src="/images/integrations/amazon.png" class="full-width full-height">
                            </div>
                        </div>
                        <div class="col-6 col-md-3 mb-4">
                            <div class="integration-box  d-flex wow fadeInDown" data-wow-delay="0.2s">
                                <img src="/images/integrations/shopify.png" class="full-width full-height"></div>
                        </div>
                        <div class="col-6 col-md-3 mb-4">
                            <div class="integration-box  d-flex wow fadeInDown" data-wow-delay="0.4s">
                                <img src="/images/integrations/vend.png" class="full-width full-height"></div>
                        </div>
                        <div class="col-6 col-md-3 mb-4">
                            <div class="integration-box  d-flex wow fadeInDown" data-wow-delay="0.6s">
                                <img src="/images/integrations/wordpress.png" class="full-width full-height"></div>
                        </div>
                        <div class="col-6 col-md-3 mb-4">
                            <div class="integration-box  d-flex wow fadeInDown" data-wow-delay="0.8s">
                                <img src="/images/integrations/ebay.png" class="full-width full-height"></div>
                        </div>
                    </div>
                </div>
                <div class="col-12">
                    <hr>
                    <h4 class="font-weight-light">Singapore</h4>
                    <div class="row pt-3">
                        <div class="col-6 col-md-3 mb-4">
                            <div class="integration-box  d-flex wow fadeInDown" data-wow-delay="0.2s">
                                <img src="/images/integrations/amazon.png" class="full-width full-height"></div>
                        </div>
                        <div class="col-6 col-md-3 mb-4">
                            <div class="integration-box  d-flex wow fadeInDown" data-wow-delay="0.4s">
                                <img src="/images/integrations/lazada.png" class="full-width full-height">
                            </div>
                        </div>
                        <div class="col-6 col-md-3 mb-4">
                            <div class="integration-box  d-flex wow fadeInDown" data-wow-delay="0.6s">
                                <img src="/images/integrations/qoo10.png" class="full-width full-height">
                            </div>
                        </div>
                        <div class="col-6 col-md-3 mb-4">
                            <div class="integration-box  d-flex wow fadeInDown" data-wow-delay="0.8s">
                                <img src="/images/integrations/shopee.png" class="full-width full-height">
                            </div>
                        </div>
                        <div class="col-6 col-md-3 mb-4">
                            <div class="integration-box  d-flex wow fadeInDown" data-wow-delay="1s">
                                <img src="/images/integrations/ebay.png" class="full-width full-height">
                            </div>
                        </div>
                        <div class="col-6 col-md-3 mb-4">
                            <div class="integration-box  d-flex wow fadeInDown" data-wow-delay="1.2s">
                                <img src="/images/integrations/redmart.png" class="full-width full-height">
                            </div>
                        </div>
                    </div>
                    <div class="col-12">
                        <hr>
                        <h4 class="font-weight-light">Malaysia</h4>
                        <div class="row d-flex pt-3">
                            <div class="col-6 col-md-3 mb-4">
                                <div class="integration-box  d-flex wow fadeInDown" data-wow-delay="0.2s">
                                    <img src="/images/integrations/amazon.png" class="full-width full-height">
                                </div>
                            </div>
                            <div class="col-6 col-md-3 mb-4">
                                <div class="integration-box  d-flex wow fadeInDown" data-wow-delay="0.4s">
                                    <img src="/images/integrations/lazada.png" class="full-width full-height">
                                </div>
                            </div>
                            <div class="col-6 col-md-3 mb-4">
                                <div class="integration-box  d-flex wow fadeInDown" data-wow-delay="0.6s">
                                    <img src="/images/integrations/qoo10.png" class="full-width full-height">
                                </div>
                            </div>
                            <div class="col-6 col-md-3 mb-4">
                                <div class="integration-box  d-flex wow fadeInDown" data-wow-delay="0.8s">
                                    <img src="/images/integrations/shopee.png" class="full-width full-height">
                                </div>
                            </div>
                            <div class="col-6 col-md-3 mb-4">
                                <div class="integration-box  d-flex wow fadeInDown" data-wow-delay="1s">
                                    <img src="/images/integrations/ebay.png"
                                            class="full-width full-height">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <h1 class="font-weight-light">Logistics</h1>
                <hr/>
                <div class="row">
                    <div class="col-md-12">

                        <h4 class="font-weight-light">Global</h4>
                        <div class="row pt-3">

                            <div class="col-6 col-md-3 mb-4">
                                <div class="integration-box  d-flex wow fadeInDown" data-wow-delay="">
                                    <img src="/images/integrations/janio.png"
                                         class="full-width full-height"/>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-12">

                        <hr/>
                        <h4 class="font-weight-light">Singapore</h4>
                        <div class="row pt-3">

                            <div class="col-6 col-md-3 mb-4">
                                <div class="integration-box d-flex wow fadeInDown" data-wow-delay="">
                                    <img src="/images/integrations/ihub.png"
                                         class="full-width full-height"/>
                                </div>
                            </div>

                            <div class="col-6 col-md-3 mb-4">
                                <div class="integration-box d-flex wow fadeInDown" data-wow-delay="0.2s">
                                    <img src="/images/integrations/singpost.png"
                                         class="full-width full-height"/>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-12">

                    <hr/>
                    <h1 class="font-weight-light">Accounting</h1>
                    <hr/>

                    <h4 class="font-weight-light">Global</h4>
                    <div class="row pt-3">

                        <div class="col-6 col-md-3 mb-4">
                            <div class="integration-box  d-flex wow fadeInDown" data-wow-delay="">
                                <img src="/images/integrations/xero.png" class="full-width full-height"/>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection