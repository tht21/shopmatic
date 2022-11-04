<template>
    <div>
        <b-row v-if="sameSkuSource">
<!--        <b-row v-if="(populated_product.unread_alerts && populated_product.unread_alerts.length > 0) || sameSkuSource">-->
            <b-col>
                <b-card body-class="pb-2">
                    <b-alert v-if="sameSkuSource" class="p-2" variant="danger" show>
                        <h3 class="m-0">
                            <i class="mr-3 fa-lg fa fa-exclamation-circle text-danger"></i>Different listings under same account with same product SKU. Edit this product will cause problems.
                        </h3>
                    </b-alert>
<!--                    <b-alert v-if="populated_product.unread_alerts && populated_product.unread_alerts.length > 0" class="p-2" v-for="alert in populated_product.unread_alerts" :key="alert.id" :variant="alertType[alert.type]" show>-->
<!--                        <h3 class="m-0">-->
<!--                            <i :class="'mr-3 fa-lg fa ' + alert.icon"></i>{{ alert.message }}-->
<!--                        </h3>-->
<!--                    </b-alert>-->
                </b-card>
            </b-col>
        </b-row>
        <b-row>
            <div class="col-md-6 col-lg-4">
                <div class="card">
                    <slick
                        class="slider-for"
                        ref="slickFor"
                        :options="slickOptionsFor">
                        <div class="image" v-for="image in populated_product.images">
                            <img
                                v-if="image.image_url && image.image_url  !== ''"
                                class="preview-img"
                                :src="image.image_url">
                            <img
                                v-else-if="image.source_url && image.source_url  !== ''"
                                class="preview-img"
                                :src="image.source_url">
                            <img
                                v-else class="preview-img"
                                :src="'/images/default.png'">
                        </div>
                    </slick>
                    <slick
                        class="slider-nav"
                        ref="slickNav"
                        :options="slickOptionsNav">
                        <div class="image"
                             v-for="image in populated_product.images">
                            <img
                                v-if="image.image_url && image.image_url  !== ''"
                                class="thumbnail-img"
                                :src="image.image_url">
                            <img
                                v-else-if="image.source_url && image.source_url  !== ''"
                                class="preview-img"
                                :src="image.source_url">
                            <img
                                v-else
                                class="thumbnail-img"
                                :src="'/images/default.png'">
                        </div>
                    </slick>
                </div>
            </div>
            <div class="col-md-6 col-lg-8">
                <b-card>
                    <b-form-select v-model="selected_listing" :options="listing_option"
                                   @change="switchListing"></b-form-select>
                </b-card>

                <div class="card">
                    <div class="card-header">
                        <h3 class="mb-0">Prices & Stock</h3>
                    </div>
                    <div class="card-body p-0 table-responsive">
                        <table class="table sticky-header">
                            <thead>
                            <tr>
                                <th class="bg-primary text-white">Name</th>
                                <th class="bg-primary text-white">Price</th>
                                <th v-if="selected_listing" class="bg-primary text-white">Type</th>
                                <th class="bg-primary text-white">Stock</th>
                                <th v-if="!selected_listing" class="bg-primary text-white">Dimensions (LxWxH)</th>
                                <th v-if="!selected_listing" class="bg-primary text-white">Weight</th>
                            </tr>
                            </thead>
                            <tbody>
                            <template v-if="selected_listing">
                                <template v-for="variant in populated_product.listing_variants">
                                    <tr v-for="price in variant.variant.prices">
                                        <template v-if="price.integration_id == selected_integration_id">
                                            <td>{{ (variant.variant.name) ? variant.variant.name : '-' }}<br/><small><strong>SKU: {{ variant.variant.sku
                                                }}</strong></small></td>
                                            <td>{{ price.currency }} {{ price.price }}</td>
                                            <td>{{ price.type }}</td>
                                            <td>{{ variant.stock }}</td>
                                        </template>
                                    </tr>
                                </template>
                            </template>
                            <template v-else>
                                <tr v-for="variant in populated_product.variants">
                                    <td>{{ (variant.name) ? variant.name : '-' }}<br/><small><strong>SKU: {{ variant.sku
                                        }}</strong></small></td>
                                    <td>{{ variant.currency }} {{ variant.price }}</td>
                                    <td>{{ variant.stock }}</td>
                                    <td>
                                        {{ variant.length }} x {{ variant.width }} x {{ variant.height }} {{ options.dimension_unit[variant.dimension_unit] }}
                                    </td>
                                    <td>
                                        {{ variant.weight }} {{ options.weight_unit[variant.weight_unit] }}
                                    </td>
                                </tr>
                            </template>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <span class="mb-0 h3">Product Variants</span>
                        <template v-if="selected_listing">
                            <!-- Do not display toogle in case of Amazon/WC product -->
                            <template v-if="![11006, 11007].includes(populated_product.integration_id)">
                                <b-form-checkbox class="float-right" @change="setEnable($event, populated_product.listing_variants, true, true)" switch :checked="checkSwitch(populated_product.listing_variants, true)"></b-form-checkbox>
                            </template>
                        </template>
                        <template v-else>
                            <b-form-checkbox class="float-right" @change="setEnable($event, populated_product.variants, false, true)" switch :checked="checkSwitch(populated_product.variants, true)" v-bind:class="{'d-none': hideToggleAll}">
                                Toggle All
                            </b-form-checkbox>
                        </template>
                    </div>
                    <div class="card-body p-0 table-responsive">
                        <table class="table sticky-header">
                            <thead>
                            <tr>
                                <th class="bg-info text-white">Name</th>
                                <template v-if="selected_listing">
                                    <th class="bg-info text-white">Account</th>
                                </template>
                                <template v-else>
                                    <th class="bg-info text-white">Dimensions (LxWxH)</th>
                                    <th class="bg-info text-white">Weight</th>
                                </template>
                                <th class="bg-info text-white">Enable/Disable</th>
                            </tr>
                            </thead>
                            <tbody>
                            <template v-if="selected_listing">
                                <tr v-for="variant in populated_product.listing_variants">
                                    <td>{{ (variant.variant.name) ? variant.variant.name : '-' }}<br/><small><strong>SKU: {{ variant.variant.sku
                                        }}</strong></small></td>
                                    <td>{{ populated_product.account.name }} <small><strong>({{ populated_product.integration.name }})</strong></small></td>
                                    <!-- If Amazon/WC don't display toogle -->
                                    <td v-if="![11006, 11007].includes(variant.integration_id)" class="text-center">
                                        <b-form-checkbox @change="setEnable($event, variant, true)" switch :checked="checkSwitch(variant)"></b-form-checkbox>
                                    </td>
                                </tr>
                            </template>
                            <template v-else>
                                <tr v-for="variant in populated_product.variants">
                                    <td>{{ (variant.name) ? variant.name : '-' }}<br/><small><strong>SKU: {{ variant.sku
                                        }}</strong></small></td>
                                    <td>
                                        {{ variant.length }} x {{ variant.width }} x {{ variant.height }} {{ options.dimension_unit[variant.dimension_unit] }}
                                    </td>
                                    <td>
                                        {{ variant.weight }} {{ options.weight_unit[variant.weight_unit] }}
                                    </td>
                                    <!-- If there is only single listing and its an Amazon/WC product hide the toggle by adding the class 'd-none' -->
                                    <td class="text-center" :class="[variant.listings.length == 1 && [11006, 11007].includes(variant.listings[0].integration_id) ? 'd-none':'']">
                                        <b-form-checkbox @change="setEnable($event, variant, false)" switch :checked="checkSwitch(variant)"></b-form-checkbox>
                                    </td>
                                </tr>
                            </template>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="card">

                    <div class="card-header">
                        <h3 class="mb-0">{{ populated_product.name }}<br/><small class="text-muted">{{
                            populated_product.associated_sku }}</small></h3>
                    </div>
                    <div class="card-body">
                        <template v-if="populated_product.short_description">
                            <h5 class="text-muted text-uppercase">SHORT DESCRIPTION</h5>
                            <div v-html="populated_product.short_description" class="description-container"></div>
                        </template>
                        <template v-if="populated_product.html_description">
                            <h5 class="text-muted text-uppercase">DESCRIPTION</h5>
                            <div id="description" class="show-more">
                                <div v-html="populated_product.html_description" class="description-container"></div>
                                <div class="read-more">
                                    <button class="btn btn-outline-dark">Show More</button>
                                </div>
                            </div>
                        </template>
                        <template v-if="populated_product.brand">
                            <h5 class="text-muted text-uppercase">Brand</h5>
                            <div v-html="populated_product.brand"></div>
                        </template>
                        <template v-if="populated_product.model">
                            <h5 class="text-muted text-uppercase">Model</h5>
                            <div v-html="populated_product.model"></div>
                        </template>

                        <hr/>
                    </div>
                </div>

                <!-- Logistics -->
                <b-card title="Selected Logistics" v-if="populated_product.logistic && populated_product.integration">
                    <b-card-text>
                        <show-logistic-component
                            :logistics="populated_product.logistic"
                            :integration="populated_product.integration">
                        </show-logistic-component>
                    </b-card-text>
                </b-card>

                <!-- Attributes -->
                <b-card title="Attributes" v-if="populated_product.attributes">
                    <b-card-text>
                        <template v-for="attribute in populated_product.attributes" v-if="!isJson(attribute.value)">
                            <h5 class="text-primary text-uppercase">{{ attribute.name }}</h5>
                            <div>
                                <p v-html="attribute.value" ></p>
                            </div>
                        </template>
                        <hr/>
                    </b-card-text>
                </b-card>
            </div>
        </b-row>
    </div>
</template>
<script>
    /*
     * TODO
     * - need to revisit the whole structure
     * - main image carousel
     * - logistic
     * - attributes
     */
    import Slick from 'vue-slick';
    import ShowLogisticComponent from './partials/ShowLogisticComponent'

    export default {
        name: "ProductDetailsComponent",
        components: {
            ShowLogisticComponent,
            Slick
        },
        props: [
            'slug'
            //'product'
        ],
        data() {
            return {
                product: [],
                retrieving_listing: false,
                sending_request: false,
                selected_integration_id: 0, // selected integration id, default general
                selected_listing: '', // selected listing, default general
                product_listings: [],
                populated_product: {}, // product details
                slickOptionsFor: {
                    slidesToShow: 1,
                    slidesToScroll: 1,
                    //infinite: true,
                    adaptiveHeight: false,
                    centerMode: true,
                    arrows: false,
                    fade: true,
                    asNavFor: '.slider-nav',
                    centerPadding: '0',
                    variableWidth: false
                    // Any other options that can be got from plugin documentation
                },
                slickOptionsNav: {
                    slidesToShow: 3,
                    slidesToScroll: 1,
                    asNavFor: '.slider-for',
                    dots: false,
                    centerMode: true,
                    focusOnSelect: true,
                    centerPadding: '0',
                    variableWidth: false
                    // Any other options that can be got from plugin documentation
                },
                options: {
                    dimension_unit: {
                        0: 'MM',
                        1: 'CM',
                        2: 'INCH',
                        3: 'FEET',
                        4: 'METER',
                    },
                    weight_unit: {
                        0: 'LB',
                        1: 'G',
                        2: 'KG',
                        3: 'OZ'
                    }
                },
                //selected_enable_variants: []
                alertType: [
                    'info',
                    'warning',
                    'danger',
                ]
            }
        },
        computed: {
            listing_option() {
                let options = [
                    {value: '', text: 'General'}
                ];
                // Append product listing
                for (let i in this.product.listings) {
                    let listing = this.product.listings[i];
                    options.push({
                        value: listing.id,
                        text: listing.account.name + ' (' + listing.account.integration.name + ')'
                    })
                }
                return options;
            },
            sameSkuSource() {
                if (this.product_listings.length > 0) {
                    let accountIds = [];
                    for (let id in this.product_listings) {
                        if (accountIds.includes(this.product_listings[id].account_id)) {
                            return true;
                        }
                        accountIds.push(this.product_listings[id].account_id);
                    }
                }
                return false;
            },
            hideToggleAll() {
                // Hide the toggle if product listing count is 1 and it's a Amazon/WC product.
                if (typeof this.populated_product.listings !=="undefined" && this.populated_product.listings.length === 1 && [11006, 11007].includes(this.populated_product.listings[0].integration_id)){
                    return true;
                }
                return false;
            },
        },
        methods: {
            switchListing() {
                // Default is general product
                if (this.selected_listing === '') {
                    this.selected_integration_id = 0;
                    this.populated_product = this.product;
                } else {
                    if (this.product_listings[this.selected_listing]) {
                        this.selected_integration_id = this.product_listings[this.selected_listing].integration_id;
                        this.populated_product = this.product_listings[this.selected_listing];
                    } else {
                        notify('top', 'Error', 'Can\'t found product listing', 'center', 'danger');
                    }
                }
            },
            retrieveProduct() {
                this.sending_request = true;
                this.product = [];
                return axios.get('/web/products/' + this.slug).then((response) => {
                    let data = response.data
                    if (data.meta.error) {
                        notify('top', 'Error', data.meta.message, 'center', 'danger');
                    } else {
                        this.product = data.response;
                        /* Check if listing available.If not available append the variant main image */
                        if (this.product.listings && this.product.listings.length) {
                            this.product.variants.map((variant) => {
                                if (variant.main_image && variant.main_image.length) {
                                    this.product.images.push({
                                        image_url: variant.main_image
                                    });
                                }
                           });
                       } else {
                           this.product.variants.map((variant) => {
                                variant.listings.map((listing)=>{
                                   listing.images.map((image) => {
                                       if (image.image_url.length) {
                                           this.product.images.push({
                                                image_url: image.image_url
                                            });
                                       }
                                   });
                                });
                           });
                       }
                       /** End  */
                    }
                    this.sending_request = false;
                }).catch((error) => {
                    console.log(error);
                    this.sending_request = false;
                    if (error.response && error.response.data && error.response.data.meta) {
                        notify('top', 'Error', error.response.data.meta.message, 'center', 'danger');
                    } else {
                        notify('top', 'Error', error, 'center', 'danger');
                    }
                });
            },
            retrieveListing() {
                this.retrieving_listing = true;
                this.product_listings = [];
                return axios.get('/web/product/listing/' + this.product.slug).then((response) => {
                    let data = response.data
                    if (data.meta.error) {
                        notify('top', 'Error', data.meta.message, 'center', 'danger');
                    } else {
                        this.product_listings = data.response;

                        if (this.product_listings && this.product_listings.length) {
                            // Filter listing variant
                            Object.values(this.product_listings).forEach((product_listing) => {
                                // Make sure the listing variant is under current listing
                                let variants = Object.values(product_listing.listing_variants).filter(function (value, index, arr) {
                                    return product_listing['identifiers'].external_id === value['identifiers'].external_id;
                                });

                                // populate listing variants data
                                product_listing['listing_variants'].forEach((variant) => {
                                    // SKU
                                    variant['sku'] = null;
                                    if (variant['identifiers'].sku) {
                                        variant['sku'] = variant['identifiers'].sku;
                                    }
                                    // Get price for variant if product listing doesnt has it
                                    let prices = variant['prices']
                                    if (prices.length == 0) {
                                        prices = variant['variant']['prices']
                                    }
                                    variant['prices'] = prices
                                    let price = prices.find(function (value) {
                                        return value.type === 'selling';
                                    });
                                    variant['currency'] = price.currency;
                                    variant['price'] = price.price;
                                });

                                // Attributes
                                let ignores = ['logistics'];
                                let attributes = product_listing['attributes'].filter(function (value) {
                                    if (!ignores.includes(value.name)) {
                                        return value;
                                    }
                                });
                                // Process attributes
                                attributes = this.processAttributes(attributes, product_listing['integration']);

                                product_listing['attributes'] = attributes;

                                // If listing images is empty, replace with default images
                                if (!product_listing.images.length) {
                                    product_listing.images = this.product.images;
                                }

                                // Replace product attribute
                                product_listing['name'] = product_listing['product'].name;
                                product_listing['brand'] = product_listing['product'].brand;
                                product_listing['html_description'] = product_listing['product'].html_description;
                                product_listing['short_description'] = product_listing['product'].short_description;
                                product_listing['associated_sku'] = product_listing['product'].associated_sku;
                            });
                        }
                    }
                    this.retrieving_listing = false;
                }).catch((error) => {
                    console.log(error);
                    this.retrieving_listing = false;
                    if (error.response && error.response.data && error.response.data.meta) {
                        notify('top', 'Error', error.response.data.meta.message, 'center', 'danger');
                    } else {
                        notify('top', 'Error', error, 'center', 'danger');
                    }
                });
            },
            processAttributes(attributes, integration) {
                let process_attributes = [];
                let ignores = [];
                let attribute_value = {};

                if (integration.id === 11006) {
                    ignores = ['dimensions', 'weight', 'description', 'shipping_required', 'shipping_taxable', 'shipping_class', 'tax_status', 'tax_class', 'manage_stock', 'sold_individually', 'menu_order', 'reviews_allowed']
                }
                if (integration.id === 11003) {
                    ignores = ['sales', 'weight', 'wholesales'];
                }
                if (integration.id === 11002) {
                    ignores = ['grams', 'weight', 'weight_unit'];
                }

                attributes.forEach((attribute) => {
                    if (!ignores.includes(attribute.name) && attribute.value) {
                        let value = attribute.value;

                        // Shopee
                        if (integration.id === 11003) {
                            if (attribute.name === 'is_pre_order') {
                                value = (attribute.value === 1) ? 'Yes' : 'No';
                            }
                        }

                        // Replace underscores
                        attribute_value = {
                            id: attribute.id,
                            name: attribute.name.replace(/_/g, ' '),
                            value: value
                        };
                        process_attributes.push(attribute_value);
                    }
                });
                return process_attributes;
            },
            setEnable (checked, variants, isListing = false, isGeneral = false) {
                //this.selected_enable_variants = [];
                if (isGeneral) {
                    if (!isListing) {
                        // General update all listing beneath this variant
                        let promises = [];
                        variants.map((variant) => {
                            variant.listings.map((listing) => {
                                //We are not toggling the status if Amazon/WC product.As Amazon doesn't supports product status changes and WC doesn't have product disable API.
                                if (typeof listing.integration_id !=="undefined" && ![11006, 11007].includes(listing.integration_id)) {
                                    const promise = this.toggleEnable(listing, checked);
                                    promises.push(promise);
                                }
                            });
                        });

                        // Retrieve again once all is updated
                        Promise.all(promises).then(() => {
                            this.populated_product = {};
                            this.retrieveProduct().then(() => {
                                // If images is empty then replace it with the main image
                                if (this.product.images.length <= 0) {
                                    this.product.images.push({
                                        image_url: this.product.main_image
                                    });
                                }
                                this.populated_product = this.product;
                                this.retrieveListing().then((result) => {
                                    this.switchListing();
                                })
                            });
                        })
                    } else {
                        // General update all listing
                        let promises = [];
                        variants.map((listing) => {
                            console.log(listing);
                            const promise = this.toggleEnable(listing, checked);
                            promises.push(promise);
                        });

                        // Retrieve again once all is updated
                        Promise.all(promises).then(() => {
                            this.populated_product = {};
                            this.retrieveProduct().then(() => {
                                // If images is empty then replace it with the main image
                                if (this.product.images.length <= 0) {
                                    this.product.images.push({
                                        image_url: this.product.main_image
                                    });
                                }
                                this.populated_product = this.product;
                                this.retrieveListing().then((result) => {
                                    this.switchListing();
                                })
                            });
                        })
                    }
                } else {
                    if (!isListing) {
                        // Update all listing beneath this variant
                        let promises = [];
                        variants.listings.map((listing) => {
                            //We are not toggling the status if Amazon product.As Amazon doesn't supports product status changes.
                                if (typeof listing.integration_id !=="undefined" && ![11006, 11007].includes(listing.integration_id)) {
                                const promise = this.toggleEnable(listing, checked);
                                promises.push(promise);
                            }
                        });

                        // Retrieve again once all is updated
                        Promise.all(promises).then(() => {
                            this.populated_product = {};
                            this.retrieveProduct().then(() => {
                                // If images is empty then replace it with the main image
                                if (this.product.images.length <= 0) {
                                    this.product.images.push({
                                        image_url: this.product.main_image
                                    });
                                }
                                this.populated_product = this.product;
                                this.retrieveListing().then((result) => {
                                    this.switchListing();
                                })
                            });
                        })
                    } else {
                        this.toggleEnable(variants, checked).then(() => {
                            // Retrieve product and listing again
                            this.populated_product = {};
                            this.retrieveProduct().then(() => {
                                // If images is empty then replace it with the main image
                                if (this.product.images.length <= 0) {
                                    this.product.images.push({
                                        image_url: this.product.main_image
                                    });
                                }
                                this.populated_product = this.product;
                                this.retrieveListing().then((result) => {
                                    this.switchListing();
                                })
                            });
                        });
                    }
                }
            },
            toggleEnable (listing, enabled) {
                notify('top', 'Info', 'Updating..', 'center', 'info');
                let parameters = {
                    enabled: enabled
                };

                return axios.put('/web/products/listings/' + listing.id + '/toggle-enable', parameters).then((response) => {
                    let data = response.data;
                    if (data.meta.error) {
                        notify('top', 'Error', data.meta.message, 'center', 'danger');
                    } else {
                        notify('top', 'Success', 'Update successfully', 'center', 'success');
                    }
                }).catch((error) => {
                    if (error.response && error.response.data && error.response.data.meta) {
                        notify('top', 'Error', error.response.data.meta.message, 'center', 'danger');
                    } else {
                        notify('top', 'Error', error, 'center', 'danger');
                    }
                });
            },
            checkSwitch(variants, isGeneral = false) {
                if (isGeneral) {
                    let count = 0;
                    if (variants) {
                        // If there is one variant is enable, then general switch should be enable as well
                        variants.map((variant) => {
                            if (variant.status === 10 || variant.status === 30) {
                                count++;
                            }
                        });
                    }
                    return count > 0;
                } else {
                    if (variants.status === 10 || variants.status === 30) {
                        return true;
                    }
                    return false;
                }
            },
            isJson(value) {
                if (typeof value !== 'string') return false;
                try {
                    const result = JSON.parse(value);
                    const type = Object.prototype.toString.call(result);
                    return type === '[object Object]'
                        || type === '[object Array]';
                } catch (err) {
                    return false;
                }
            }
        },
        created() {
            this.retrieveProduct().then(() => {
                // If images is empty then replace it with the main image
                if (this.product.images.length <= 0) {
                    this.product.images.push({
                        image_url: this.product.main_image
                    });
                }
                if(this.product.main_image){
                    this.product.images.push({'image_url': this.product.main_image})
                }
                this.populated_product = this.product;
                this.retrieveListing()
            });
        },
        watch: {
            populated_product() {
                if (typeof this.$refs.slickFor !== 'undefined') {
                    let currIndex = this.$refs.slickFor.currentSlide();

                    this.$refs.slickFor.destroy();
                    this.$nextTick(() => {
                        this.$refs.slickFor.create();
                        this.$refs.slickFor.goTo(currIndex, true);
                    });

                    let currIndex1 = this.$refs.slickNav.currentSlide();

                    this.$refs.slickNav.destroy();
                    setTimeout(() => {
                        this.$nextTick(() => {
                            this.$refs.slickNav.create();
                            this.$refs.slickNav.goTo(currIndex1, true);
                        })
                    }, 20);
                }
            }
        }
    }
</script>

<style>
    .description-container {
        overflow-x: scroll;
    }
    .preview-img {
        margin: 0 auto;
        max-width: 100%;
    }
    .thumbnail-img {
        max-width: 100%;
    }
    .slick-slide {
        padding-left: 6px;
        padding-right: 6px;
    }

    .slick-prev:before {
        color: blue;
    }

    .slick-next:before {
        color: blue;
    }
</style>
