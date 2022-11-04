<template>
    <div>
        <div :class="{'disable-pointer': customize}">
            <vue-dropzone
                class="dropzone-images"
                :id="id" :ref="id"
                :options="customOptions"
                :useCustomSlot=true
                :duplicateCheck=true
                :destroyDropzone=false
                @vdropzone-mounted="loadImages"
                @vdropzone-file-added="updateImages"
                @vdropzone-removed-file="removeImage"
                @vdropzone-duplicate-file="duplicated"
                @vdropzone-error="showError">
                <div>
                    <h3 class="mt-0">Drag and drop to upload image!</h3>
                    <div>...or click to select a file from your computer</div>
                </div>
            </vue-dropzone>
        </div>
        <template v-if="typeof disabled === 'boolean'">
            <b-link @click="setCustomize"><u>{{customize? 'Customize' : 'Default'}}</u></b-link>
        </template>
    </div>
</template>

<script>
    import isEqual from "lodash/isEqual";

    export default {
        name: "VueDropzoneImage",
        props: {
            // can be synced with parent model
            model: {
                type: Array,
                required: true
            },
            initModel: {
                type: Array,
                default: undefined
            },
            validator: {
                type: Object,
                default: undefined
            },
            // normal props
            id: {
                type: String,
                default: 'dropzone-image'
            },
            options: {
                type: Object,
                default: () => ({})
            },
            disabled: {
                type: Boolean,
                default: undefined
            },
        },
        data() {
            return {
                images: [],
                defaultOptions: {
                    url: 'https://',
                    thumbnailWidth: 200,
                    autoProcessQueue: false,
                    addRemoveLinks: true,
                    acceptedFiles: 'image/jpeg, image/png'
                },
                customize: this.disabled
            }
        },
        computed: {
            customOptions() {
                return {...this.defaultOptions, ...this.options}
            }
        },
        methods: {
            loadImages() {
                for (let i in this.model) {
                    if (this.model[i].hasOwnProperty('image_url')) {
                        // pre-load image from given url
                        // setup file type (must have size property)
                        let file = {
                            name: 'Image ' + (parseInt(i) + 1),
                            size: 0,
                            image_url: this.model[i].image_url,
                            width: this.model[i].width,
                            height: this.model[i].height
                        };

                        if (this.model[i].hasOwnProperty('deleted') && this.model[i].deleted) {
                            this.images.push(this.model[i]);
                        } else {
                            // manual add file to dropzone with thumbnail
                            this.$refs[this.id].manuallyAddFile(file, file.image_url);
                        }
                    } else if (this.model[i].hasOwnProperty('data_url')) {
                        // pre-load image from base64 string
                        this.$refs[this.id].addFile(this.dataURItoBlob(this.model[i].data_url));
                    }
                }
                this.updateModel();
            },
            async updateImages(image) {
                if (image.hasOwnProperty('image_url')) {
                    // TODO: ltr change this after confirm the format
                    // existing image in cloud storage
                    this.images.push({
                        image_url: image.image_url,
                        width: image.width,
                        height: image.height
                    });
                } else {
                    // new image
                    await new Promise(resolve => {
                        // setup file reader
                        let fileReader = new FileReader();
                        fileReader.onloadend = async () => {
                            // setup image element
                            await new Promise( resolve => {
                                let img = new Image();
                                img.onload = () => {

                                    this.images.push({
                                        data_url: img.src,
                                        width: img.width,
                                        height: img.height
                                    });
                                    resolve();
                                };

                                // set dataURL to src
                                img.src = fileReader.result;
                            });
                            resolve();
                        };

                        // read file data
                        fileReader.readAsDataURL(image);
                    });
                }
                // console.log(image);

                this.updateModel();
            },
            updateModel() {
                this.$emit('update:model', this.images);
                this.$emit('input', this.images);
                if (typeof this.validator !== 'undefined') {
                    this.$emit('update:validator', {invalid: this.images.length === 0, dirty: this.isDirty()});
                }
            },
            setCustomize() {
                this.customize = !this.customize;
                this.$emit('update:disabled', this.customize);
                this.$emit('customize', this.customize);
            },
            isDirty() {
                if (typeof this.validator !== "undefined" && typeof this.initModel !== "undefined") {
                    return !isEqual(this.images, this.initModel);
                }
                return null;
            },
            duplicated(image) {
                notify('top', 'Error', image.name + ' already exist.', 'center', 'danger');
            },
            removeImage(image) {
                for (let index = 0; index < this.images.length; index++) {
                    if (image.hasOwnProperty('image_url') && this.images[index].hasOwnProperty('image_url') && image.image_url === this.images[index].image_url) {
                        this.images[index]['deleted'] = true;
                        break;
                    } else if (image.hasOwnProperty('dataURL') && this.images[index].hasOwnProperty('data_url') && image.dataURL === this.images[index].data_url) {
                        this.images.splice(index, 1);
                        break;
                    }
                }
                this.updateModel();
            },
            showError(file, message, xhr) {
                if (typeof xhr === 'undefined') {
                    this.$refs[this.id].removeFile(file);
                }

                // ignored upload error since we use our api to upload image
                if (message !== 'Upload canceled.') {
                    notify('top', 'Error', message, 'center', 'danger');
                }
            },
            dataURItoBlob(dataURI) {
                let byteString = null;

                if(dataURI.split(',')[0].indexOf('base64') !== -1 ) {
                    byteString = atob(dataURI.split(',')[1])
                } else {
                    byteString = decodeURI(dataURI.split(',')[1])
                }

                let mimestring = dataURI.split(',')[0].split(':')[1].split(';')[0];

                let content = [];
                for (let i = 0; i < byteString.length; i++) {
                    content[i] = byteString.charCodeAt(i);
                }

                return new Blob([new Uint8Array(content)], {type: mimestring});
            }
        }
    }
</script>

<style scoped>
    .dropzone-images {
        display: block;
    }

    .dropzone-images .dz-message > div {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        text-align: center;
    }

    .dropzone-images >>> .dz-image-preview .dz-progress {
        visibility: hidden;
    }

    .disable-pointer {
        pointer-events: none;
        opacity: 0.4;
    }
</style>
