<template>
    <div id="media">
        <content-inner>
            <ul slot="breadcrumb">
                <li><a href="#/components/">Home</a></li>
                <li><a href="#/components/components/">Components</a></li>
                <li>Media</li>
            </ul>

            <container>
                <section id="vue">
                    <page-heading>Media</page-heading>

                    <preformatted title="Required Components">path/to/<strong>components/Media.vue</strong></preformatted>

<preformatted title="Props"><strong>mediaType</strong>: {
    type: String,
    default: "image"
},

<strong>src</strong>: {
    type: String,
    required: true
},

<strong>rounded</strong>: {
    type: Boolean,
    default: false
},

<strong>height</strong>: {
    type: Number,
    default: 100
},

<strong>width</strong>: {
    type: Number,
    default: 100
},

<strong>pixelHeight</strong>: {
    type: Boolean,
    default: false
},

<strong>pixelWidth</strong>: {
    type: Boolean,
    default: false
}</preformatted>
                </section>

                <section id="image">
                    <subheading>Image</subheading>

<preformatted title="Props"><strong>circle</strong>: {
    type: Boolean,
    default: false
}</preformatted>

                    <well>
                        <preformatted title="Default">&lt;<strong>media</strong> <strong>src</strong>="path/to/image"&gt;&lt;/<strong>media</strong>&gt;</preformatted>

                        <media src="/src/assets/background-image.jpg"></media>

                        <preformatted title="Rounded">&lt;media src="path/to/image" <strong>:rounded</strong>="<strong>true</strong>"&gt;&lt;/media&gt;</preformatted>

                        <media src="/src/assets/background-image.jpg" :rounded="true"></media>

                        <preformatted title="Circle">&lt;media src="path/to/image" <strong>:circle</strong>="<strong>true</strong>" :pixel-width="true" :width="300"&gt;&lt;/media&gt;</preformatted>

                        <media src="/src/assets/background-image.jpg" :circle="true" :pixel-width="true" :width="300"></media>

                        <preformatted title="Title and Caption">&lt;media src="path/to/image" <strong>title</strong>="Title"&gt;<strong>Caption</strong>&lt;/media&gt;</preformatted>

                        <media src="/src/assets/background-image.jpg" title="Image Title">Lorem ipsum dolor sit amet, consectetur adipiscing elit. Phasellus turpis dolor, venenatis tincidunt imperdiet eu, condimentum eget dui. Mauris vitae nisi dui. Duis a orci quis enim dapibus venenatis.</media>
                    </well>
                </section>

                <section id="video">
                    <subheading>Video</subheading>

<preformatted title="Props"><strong>autoplay</strong>: {
    type: Boolean,
    default: false
},

<strong>loop</strong>: {
    type: Boolean,
    default: false
},

<strong>controls</strong>: {
    type: Boolean,
    default: true
},

<strong>poster</strong>: {
    type: String
},

<strong>muted</strong>: {
    type: Boolean,
    default: false
}</preformatted>

                    <well>
                        <preformatted title="Default">&lt;media <strong>type</strong>="<strong>video</strong>" <strong>:autoplay</strong>="<strong>true</strong>" <strong>:loop</strong>="<strong>true</strong>" <strong>:controls</strong>="<strong>true</strong>" src="path/to/video"&gt;&lt;/media&gt;</preformatted>

                        <media type="video" src="/src/assets/demo-video.mp4"></media>

                        <preformatted title="Rounded">&lt;media <strong>type</strong>="<strong>video</strong>" <strong>:rounded</strong>="<strong>true</strong>" src="path/to/video"&gt;&lt;/media&gt;</preformatted>

                        <media type="video" :rounded="true" src="/src/assets/demo-video.mp4"></media>

                        <preformatted title="Poster">&lt;media <strong>type</strong>="<strong>video</strong>" <strong>poster</strong>="path/to/thumbnail" src="path/to/video"&gt;&lt;/media&gt;</preformatted>

                        <media type="video" :rounded="true" poster="/src/assets/video-thumbnail.jpg" src="/src/assets/demo-video.mp4"></media>

                        <preformatted title="Loop">&lt;media <strong>type</strong>="<strong>video</strong>" <strong>:loop</strong>="<strong>true</strong>" src="path/to/video"&gt;&lt;/media&gt;</preformatted>

                        <media type="video" :rounded="true" :loop="true" src="/src/assets/demo-video.mp4"></media>

                        <preformatted title="Autoplay">&lt;media <strong>type</strong>="<strong>video</strong>" <strong>:autoplay</strong>="<strong>true</strong>" :controls="false" src="path/to/video"&gt;&lt;/media&gt;</preformatted>

                        <media type="video" :rounded="true" :autoplay="true" :loop="true" :controls="false" src="/src/assets/demo-video.mp4"></media>

                        <preformatted title="Title and Caption">&lt;media <strong>type</strong>="<strong>video</strong>" src="path/to/video" <strong>title</strong>="Title"&gt;<strong>Caption</strong>&lt;/media&gt;</preformatted>

                        <media type="video" src="/src/assets/demo-video.mp4" title="Video Title">Lorem ipsum dolor sit amet, consectetur adipiscing elit. Phasellus turpis dolor, venenatis tincidunt imperdiet eu, condimentum eget dui. Mauris vitae nisi dui. Duis a orci quis enim dapibus venenatis.</media>
                    </well>
                </section>

                <section id="audio">
                    <subheading>Audio</subheading>

<preformatted title="Props"><strong>autoplay</strong>: {
    type: Boolean,
    default: false
},

<strong>loop</strong>: {
    type: Boolean,
    default: false
},

<strong>controls</strong>: {
    type: Boolean,
    default: true
},

<strong>muted</strong>: {
    type: Boolean,
    default: false
}</preformatted>

                    <well>
                        <preformatted title="Default">&lt;media <strong>type</strong>="<strong>audio</strong>" <strong>src</strong>="path/to/audio"&gt;&lt;/media&gt;</preformatted>

                        <media type="audio" src="#"></media>

                        <preformatted title="Title and Caption">&lt;media <strong>type</strong>="<strong>audio</strong>" src="path/to/audio" <strong>title</strong>="Title"&gt;<strong>Caption</strong>&lt;/media&gt;</preformatted>

                        <media type="audio" src="#" title="Audio Title">Lorem ipsum dolor sit amet, consectetur adipiscing elit. Phasellus turpis dolor, venenatis tincidunt imperdiet eu, condimentum eget dui. Mauris vitae nisi dui. Duis a orci quis enim dapibus venenatis.</media>
                    </well>
                </section>
            </container>
        </content-inner>

        <sidebar>
            <list-group>
                <div slot="title">Configurations</div>

                <list-item>
                    <a href="#image">Image</a>
                </list-item>

                <list-item>
                    <a href="#video">Video</a>
                </list-item>

                <list-item>
                    <a href="#audio">Audio</a>
                </list-item>
            </list-group>
        </sidebar>
    </div>
</template>

<script>
    const importedComponents = {
        ContentInner: use('./../Structure/ContentInner.vue'),
        Sidebar: use('./../Structure/Sidebar.vue'),
        Container: use('EntradaJS/Components/Layout/Container.vue'),
        ListGroup: use('EntradaJS/Components/ListGroup.vue'),
        ListItem: use('EntradaJS/Components/ListItem.vue'),
        Media: use('EntradaJS/Components/Media.vue'),
        PageHeading: use('EntradaJS/Components/PageHeading.vue'),
        Preformatted: use('EntradaJS/Components/Preformatted.vue'),
        Subheading: use('EntradaJS/Components/Subheading.vue'),
        Well: use('EntradaJS/Components/Well.vue')
    };

    module.exports = {
        name: "ImageDocumentation",

        components: importedComponents,

        methods: {
            goBack () {
                window.history.length > 1
                ? this.$router.go(-1)
                : this.$router.push('/')
            }
        },

        created () {
            this.$emit('showing');
        },

        destroyed() {
            this.$emit('showing');
        }
    }
</script>
