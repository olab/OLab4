<template>
    <div id="layouts">
        <content-inner>
            <ul slot="breadcrumb">
                <li><a href="#/components/">Home</a></li>
                <li><a href="#/components/components/">Components</a></li>
                <li>Layouts</li>
            </ul>

            <container>
                <page-heading>Layouts</page-heading>

                <section id="full-width">
                    <subheading>Full Width</subheading>

<preformatted title="Required Components">path/to/<strong>components/layout/Row.vue</strong>
path/to/<strong>components/layout/FullWidth.vue</strong></preformatted>

                    <well>
                        <row>
                            <full-width>
                                <single-line-input id="full-width" type="text" :hide-label="true" placeholder="Full Width" :readonly="true">Full Width</single-line-input>
                            </full-width>
                        </row>

                        <row>
                            <full-width>
<preformatted>&lt;<strong>row</strong>&gt;
    &lt;<strong>full-width</strong>&gt;
        // content
    &lt;/<strong>full-width</strong>&gt;
&lt;/<strong>row</strong>&gt;</preformatted>
                            </full-width>
                        </row>
                    </well>
                </section>

                <section id="half">
                    <subheading>Half</subheading>

<preformatted title="Required Components">path/to/<strong>components/layout/Row.vue</strong>
path/to/<strong>components/layout/OneHalf.vue</strong></preformatted>

                    <well>
                        <row>
                            <one-half>
                                <single-line-input id="one-half-1" type="text" :hide-label="true" placeholder="One Half" :readonly="true">One Half 1</single-line-input>
                            </one-half>

                            <one-half>
                                <single-line-input id="one-half-2" type="text" :hide-label="true" placeholder="One Half" :readonly="true">One Half 2</single-line-input>
                            </one-half>
                        </row>

                        <row>
                            <full-width>
<preformatted>&lt;<strong>row</strong>&gt;
    &lt;<strong>one-half</strong>&gt;
        // content
    &lt;/<strong>one-half</strong>&gt;

    &lt;<strong>one-half</strong>&gt;
        // content
    &lt;/<strong>one-half</strong>&gt;
&lt;/<strong>row</strong>&gt;</preformatted>
                            </full-width>
                        </row>
                    </well>
                </section>

                <section id="thirds">
                    <subheading>Thirds</subheading>

<preformatted title="Required Components">path/to/<strong>components/layout/Row.vue</strong>
path/to/<strong>components/layout/OneThird.vue</strong>
path/to/<strong>components/layout/TwoThirds.vue</strong></preformatted>

                    <well>
                        <row>
                            <one-third>
                                <single-line-input id="one-third-1" type="text" :hide-label="true" placeholder="One Third" :readonly="true">One Third 1</single-line-input>
                            </one-third>

                            <one-third>
                                <single-line-input id="one-third-2" type="text" :hide-label="true" placeholder="One Third" :readonly="true">One Third 2</single-line-input>
                            </one-third>

                            <one-third>
                                <single-line-input id="one-third-3" type="text" :hide-label="true" placeholder="One Third" :readonly="true">One Third 3</single-line-input>
                            </one-third>
                        </row>

                        <row>
                            <full-width>
<preformatted>&lt;<strong>row</strong>&gt;
    &lt;<strong>one-third</strong>&gt;
        // content
    &lt;/<strong>one-third</strong>&gt;

    &lt;<strong>one-third</strong>&gt;
        // content
    &lt;/<strong>one-third</strong>&gt;

    &lt;<strong>one-third</strong>&gt;
        // content
    &lt;/<strong>one-third</strong>&gt;
&lt;/<strong>row</strong>&gt;</preformatted>
                            </full-width>
                        </row>
                    </well>

                    <well>
                        <row>
                            <one-third>
                                <single-line-input id="two-thirds-1" type="text" :hide-label="true" placeholder="One Third" :readonly="true">Two Thirds 1</single-line-input>
                            </one-third>

                            <two-thirds>
                                <single-line-input id="two-thirds-2" type="text" :hide-label="true" placeholder="Two Thirds" :readonly="true">Two Thirds 2</single-line-input>
                            </two-thirds>
                        </row>

                        <row>
                            <full-width>
<preformatted>&lt;<strong>row</strong>&gt;
    &lt;<strong>one-third</strong>&gt;
        // content
    &lt;/<strong>one-third</strong>&gt;

    &lt;<strong>two-thirds</strong>&gt;
        // content
    &lt;/<strong>two-thirds</strong>&gt;
&lt;/<strong>row</strong>&gt;</preformatted>
                            </full-width>
                        </row>
                    </well>
                </section>

                <section id="quarters">
                    <subheading>Quarters</subheading>

<preformatted title="Required Components">path/to/<strong>components/layout/Row.vue</strong>
path/to/<strong>components/layout/OneQuarter.vue</strong>
path/to/<strong>components/layout/ThreeQuarters.vue</strong></preformatted>

                    <well>
                        <row>
                            <one-quarter>
                                <single-line-input id="one-quarter-1" type="text" :hide-label="true" placeholder="One Quarter" :readonly="true">One Quarter 1</single-line-input>
                            </one-quarter>

                            <one-quarter>
                                <single-line-input id="one-quarter-2" type="text" :hide-label="true" placeholder="One Quarter" :readonly="true">One Quarter 2</single-line-input>
                            </one-quarter>

                            <one-quarter>
                                <single-line-input id="one-quarter-3" type="text" :hide-label="true" placeholder="One Quarter" :readonly="true">One Quarter 3</single-line-input>
                            </one-quarter>

                            <one-quarter>
                                <single-line-input id="one-quarter-4" type="text" :hide-label="true" placeholder="One Quarter" :readonly="true">One Quarter 4</single-line-input>
                            </one-quarter>
                        </row>

                        <row>
                            <full-width>
<preformatted>&lt;<strong>row</strong>&gt;
    &lt;<strong>one-quarter</strong>&gt;
        // content
    &lt;/<strong>one-quarter</strong>&gt;

    &lt;<strong>one-quarter</strong>&gt;
        // content
    &lt;/<strong>one-quarter</strong>&gt;

    &lt;<strong>one-quarter</strong>&gt;
        // content
    &lt;/<strong>one-quarter</strong>&gt;

    &lt;<strong>one-quarter</strong>&gt;
        // content
    &lt;/<strong>one-quarter</strong>&gt;
&lt;/<strong>row</strong>&gt;</preformatted>
                            </full-width>
                        </row>
                    </well>

                    <well>
                        <row>
                            <one-quarter>
                                <single-line-input id="three-quarters-1" type="text" :hide-label="true" placeholder="One Quarter" :readonly="true">Three Quarters 1</single-line-input>
                            </one-quarter>

                            <three-quarters>
                                <single-line-input id="three-quarters-2" type="text" :hide-label="true" placeholder="Three Quarters" :readonly="true">Three Quarters 2</single-line-input>
                            </three-quarters>
                        </row>

                        <row>
                            <full-width>
<preformatted>&lt;<strong>row</strong>&gt;
    &lt;<strong>one-quarter</strong>&gt;
        // content
    &lt;/<strong>one-quarter</strong>&gt;

    &lt;<strong>three-quarters</strong>&gt;
        // content
    &lt;/<strong>three-quarters</strong>&gt;
&lt;/<strong>row</strong>&gt;</preformatted>
                            </full-width>
                        </row>
                    </well>
                </section>

                <section id="fifths">
                    <subheading>Fifths</subheading>

<preformatted title="Required Components">path/to/<strong>components/layout/Row.vue</strong>
path/to/<strong>components/layout/OneFifth.vue</strong>
path/to/<strong>components/layout/TwoFifths.vue</strong>
path/to/<strong>components/layout/ThreeFifths.vue</strong>
path/to/<strong>components/layout/FourFifths.vue</strong></preformatted>

                    <well>
                        <row>
                            <one-fifth>
                                <single-line-input id="one-fifth-1" type="text" :hide-label="true" placeholder="One Fifth" :readonly="true">One Fifth 1</single-line-input>
                            </one-fifth>

                            <one-fifth>
                                <single-line-input id="one-fifth-2" type="text" :hide-label="true" placeholder="One Fifth" :readonly="true">One Fifth 2</single-line-input>
                            </one-fifth>

                            <one-fifth>
                                <single-line-input id="one-fifth-3" type="text" :hide-label="true" placeholder="One Fifth" :readonly="true">One Fifth 3</single-line-input>
                            </one-fifth>

                            <one-fifth>
                                <single-line-input id="one-fifth-4" type="text" :hide-label="true" placeholder="One Fifth" :readonly="true">One Fifth 4</single-line-input>
                            </one-fifth>

                            <one-fifth>
                                <single-line-input id="one-fifth-5" type="text" :hide-label="true" placeholder="One Fifth" :readonly="true">One Fifth 5</single-line-input>
                            </one-fifth>
                        </row>

                        <row>
                            <full-width>
<preformatted>&lt;<strong>row</strong>&gt;
    &lt;<strong>one-fifth</strong>&gt;
        // content
    &lt;/<strong>one-fifth</strong>&gt;

    &lt;<strong>one-fifth</strong>&gt;
        // content
    &lt;/<strong>one-fifth</strong>&gt;

    &lt;<strong>one-fifth</strong>&gt;
        // content
    &lt;/<strong>one-fifth</strong>&gt;

    &lt;<strong>one-fifth</strong>&gt;
        // content
    &lt;/<strong>one-fifth</strong>&gt;

    &lt;<strong>one-fifth</strong>&gt;
        // content
    &lt;/<strong>one-fifth</strong>&gt;
&lt;/<strong>row</strong>&gt;</preformatted>
                            </full-width>
                        </row>
                    </well>

                    <well>
                        <row>
                            <one-fifth>
                                <single-line-input id="two-fifths-2" type="text" :hide-label="true" placeholder="One Fifth" :readonly="true">Two Fifths 2</single-line-input>
                            </one-fifth>

                            <two-fifths>
                                <single-line-input id="two-fifths-1" type="text" :hide-label="true" placeholder="Two Fifths" :readonly="true">Two Fifths 1</single-line-input>
                            </two-fifths>

                            <two-fifths>
                                <single-line-input id="two-fifths-3" type="text" :hide-label="true" placeholder="Two Fifths" :readonly="true">Two Fifths 3</single-line-input>
                            </two-fifths>
                        </row>

                        <row>
                            <full-width>
<preformatted>&lt;<strong>row</strong>&gt;
    &lt;<strong>one-fifth</strong>&gt;
        // content
    &lt;/<strong>one-fifth</strong>&gt;

    &lt;<strong>two-fifths</strong>&gt;
        // content
    &lt;/<strong>two-fifths</strong>&gt;

    &lt;<strong>two-fifths</strong>&gt;
        // content
    &lt;/<strong>two-fifths</strong>&gt;
&lt;/<strong>row</strong>&gt;</preformatted>
                            </full-width>
                        </row>
                    </well>

                    <well>
                        <row>
                            <one-fifth>
                                <single-line-input id="three-fifths-1" type="text" :hide-label="true" placeholder="One Fifth" :readonly="true">Three Fifths 1</single-line-input>
                            </one-fifth>

                            <one-fifth>
                                <single-line-input id="three-fifths-3" type="text" :hide-label="true" placeholder="One Fifth" :readonly="true">Three Fifths 3</single-line-input>
                            </one-fifth>

                            <three-fifths>
                                <single-line-input id="three-fifths-2" type="text" :hide-label="true" placeholder="Three Fifths" :readonly="true">Three Fifths 2</single-line-input>
                            </three-fifths>
                        </row>

                        <row>
                            <full-width>
<preformatted>&lt;<strong>row</strong>&gt;
    &lt;<strong>one-fifth</strong>&gt;
        // content
    &lt;/<strong>one-fifth</strong>&gt;

    &lt;<strong>one-fifth</strong>&gt;
        // content
    &lt;/<strong>one-fifth</strong>&gt;

    &lt;<strong>three-fifths</strong>&gt;
        // content
    &lt;/<strong>three-fifths</strong>&gt;
&lt;/<strong>row</strong>&gt;</preformatted>
                            </full-width>
                        </row>
                    </well>

                    <well>
                        <row>
                            <one-fifth>
                                <single-line-input id="four-fifths-1" type="text" :hide-label="true" placeholder="One Fifth" :readonly="true">Four Fifths 1</single-line-input>
                            </one-fifth>

                            <four-fifths>
                                <single-line-input id="four-fifths-2" type="text" :hide-label="true" placeholder="Four Fifths" :readonly="true">Four Fifths 2</single-line-input>
                            </four-fifths>
                        </row>

                        <row>
                            <full-width>
<preformatted>&lt;<strong>row</strong>&gt;
    &lt;<strong>one-fifth</strong>&gt;
        // content
    &lt;/<strong>one-fifth</strong>&gt;

    &lt;<strong>four-fifths</strong>&gt;
        // content
    &lt;/<strong>four-fifths</strong>&gt;
&lt;/<strong>row</strong>&gt;</preformatted>
                            </full-width>
                        </row>
                    </well>
                </section>

                <section id="sixths">
                    <subheading>Sixths</subheading>

<preformatted title="Required Components">path/to/<strong>components/layout/Row.vue</strong>
path/to/<strong>components/layout/OneSixth.vue</strong>
path/to/<strong>components/layout/FiveSixths.vue</strong></preformatted>

                    <well>
                        <row>
                            <one-sixth>
                                <single-line-input id="one-sixth-1" type="text" :hide-label="true" placeholder="One Sixth" :readonly="true">One Sixth 1</single-line-input>
                            </one-sixth>

                            <one-sixth>
                                <single-line-input id="one-sixth-2" type="text" :hide-label="true" placeholder="One Sixth" :readonly="true">One Sixth 2</single-line-input>
                            </one-sixth>

                            <one-sixth>
                                <single-line-input id="one-sixth-3" type="text" :hide-label="true" placeholder="One Sixth" :readonly="true">One Sixth 3</single-line-input>
                            </one-sixth>

                            <one-sixth>
                                <single-line-input id="one-sixth-4" type="text" :hide-label="true" placeholder="One Sixth" :readonly="true">One Sixth 4</single-line-input>
                            </one-sixth>

                            <one-sixth>
                                <single-line-input id="one-sixth-5" type="text" :hide-label="true" placeholder="One Sixth" :readonly="true">One Sixth 5</single-line-input>
                            </one-sixth>

                            <one-sixth>
                                <single-line-input id="one-sixth-6" type="text" :hide-label="true" placeholder="One Sixth" :readonly="true">One Sixth 6</single-line-input>
                            </one-sixth>
                        </row>

                        <row>
                            <full-width>
<preformatted>&lt;<strong>row</strong>&gt;
    &lt;<strong>one-sixth</strong>&gt;
        // content
    &lt;/<strong>one-sixth</strong>&gt;

    &lt;<strong>one-sixth</strong>&gt;
        // content
    &lt;/<strong>one-sixth</strong>&gt;

    &lt;<strong>one-sixth</strong>&gt;
        // content
    &lt;/<strong>one-sixth</strong>&gt;

    &lt;<strong>one-sixth</strong>&gt;
        // content
    &lt;/<strong>one-sixth</strong>&gt;

    &lt;<strong>one-sixth</strong>&gt;
        // content
    &lt;/<strong>one-sixth</strong>&gt;

    &lt;<strong>one-sixth</strong>&gt;
        // content
    &lt;/<strong>one-sixth</strong>&gt;
&lt;/<strong>row</strong>&gt;</preformatted>
                            </full-width>
                        </row>
                    </well>

                    <well>
                        <row>
                            <one-sixth>
                                <single-line-input id="five-sixths-1" type="text" :hide-label="true" placeholder="One Sixth" :readonly="true">Five Sixth 1</single-line-input>
                            </one-sixth>

                            <five-sixths>
                                <single-line-input id="five-sixths-2" type="text" :hide-label="true" placeholder="Five Sixths" :readonly="true">Five Sixth 2</single-line-input>
                            </five-sixths>
                        </row>

                        <row>
                            <full-width>
<preformatted>&lt;<strong>row</strong>&gt;
    &lt;<strong>one-sixth</strong>&gt;
        // content
    &lt;/<strong>one-sixth</strong>&gt;

    &lt;<strong>five-sixths</strong>&gt;
        // content
    &lt;/<strong>five-sixths</strong>&gt;
&lt;/<strong>row</strong>&gt;</preformatted>
                            </full-width>
                        </row>
                    </well>
                </section>

                <section id="combinations">
                    <subheading>Combinations</subheading>

                    <well>
                        <row>
                            <one-quarter>
                                <single-line-input id="combo-1" type="text" :hide-label="true" placeholder="One Quarter" :readonly="true">Combo 1</single-line-input>
                            </one-quarter>

                            <one-quarter>
                                <single-line-input id="combo-2" type="text" :hide-label="true" placeholder="One Quarter" :readonly="true">Combo 2</single-line-input>
                            </one-quarter>

                            <one-half>
                                <single-line-input id="combo-3" type="text" :hide-label="true" placeholder="One Half" :readonly="true">Combo 3</single-line-input>
                            </one-half>
                        </row>

                        <row>
                            <full-width>
<preformatted>&lt;<strong>row</strong>&gt;
    &lt;<strong>one-quarter</strong>&gt;
        // content
    &lt;/<strong>one-quarter</strong>&gt;

    &lt;<strong>one-quarter</strong>&gt;
        // content
    &lt;/<strong>one-quarter</strong>&gt;

    &lt;<strong>one-half</strong>&gt;
        // content
    &lt;/<strong>one-half</strong>&gt;
&lt;/<strong>row</strong>&gt;</preformatted>
                            </full-width>
                        </row>
                    </well>

                    <well>
                        <row>
                            <one-quarter>
                                <single-line-input id="combo-1" type="text" :hide-label="true" placeholder="One Quarter" :readonly="true">Combo 1</single-line-input>
                            </one-quarter>

                            <one-quarter>
                                <single-line-input id="combo-2" type="text" :hide-label="true" placeholder="One Quarter" :readonly="true">Combo 2</single-line-input>
                            </one-quarter>

                            <one-sixth>
                                <single-line-input id="combo-3" type="text" :hide-label="true" placeholder="One Sixth" :readonly="true">Combo 3</single-line-input>
                            </one-sixth>

                            <one-sixth>
                                <single-line-input id="combo-3" type="text" :hide-label="true" placeholder="One Sixth" :readonly="true">Combo 3</single-line-input>
                            </one-sixth>

                            <one-sixth>
                                <single-line-input id="combo-3" type="text" :hide-label="true" placeholder="One Sixth" :readonly="true">Combo 3</single-line-input>
                            </one-sixth>
                        </row>

                        <row>
                            <full-width>
<preformatted>&lt;<strong>row</strong>&gt;
    &lt;<strong>one-quarter</strong>&gt;
        // content
    &lt;/<strong>one-quarter</strong>&gt;

    &lt;<strong>one-quarter</strong>&gt;
        // content
    &lt;/<strong>one-quarter</strong>&gt;

    &lt;<strong>one-sixth</strong>&gt;
        // content
    &lt;/<strong>one-sixth</strong>&gt;

    &lt;<strong>one-sixth</strong>&gt;
        // content
    &lt;/<strong>one-sixth</strong>&gt;

    &lt;<strong>one-sixth</strong>&gt;
        // content
    &lt;/<strong>one-sixth</strong>&gt;
&lt;/<strong>row</strong>&gt;</preformatted>
                            </full-width>
                        </row>
                    </well>

                    <well>
                        <row>
                            <one-quarter>
                                <single-line-input id="combo-1" type="text" :hide-label="true" placeholder="One Quarter" :readonly="true">Combo 1</single-line-input>
                            </one-quarter>

                            <one-quarter>
                                <single-line-input id="combo-2" type="text" :hide-label="true" placeholder="One Quarter" :readonly="true">Combo 2</single-line-input>
                            </one-quarter>

                            <one-sixth>
                                <single-line-input id="combo-3" type="text" :hide-label="true" placeholder="One Sixth" :readonly="true">Combo 3</single-line-input>
                            </one-sixth>

                            <one-third>
                                <single-line-input id="combo-3" type="text" :hide-label="true" placeholder="One Third" :readonly="true">Combo 3</single-line-input>
                            </one-third>
                        </row>

                        <row>
                            <full-width>
<preformatted>&lt;<strong>row</strong>&gt;
    &lt;<strong>one-quarter</strong>&gt;
        // content
    &lt;/<strong>one-quarter</strong>&gt;

    &lt;<strong>one-quarter</strong>&gt;
        // content
    &lt;/<strong>one-quarter</strong>&gt;

    &lt;<strong>one-sixth</strong>&gt;
        // content
    &lt;/<strong>one-sixth</strong>&gt;

    &lt;<strong>one-third</strong>&gt;
        // content
    &lt;/<strong>one-third</strong>&gt;
&lt;/<strong>row</strong>&gt;</preformatted>
                            </full-width>
                        </row>
                    </well>

                    <well>
                        <row>
                            <one-half>
                                <single-line-input id="combo-1" type="text" :hide-label="true" placeholder="One Half>" :readonly="true">Combo 1</single-line-input>
                            </one-half>

                            <one-sixth>
                                <single-line-input id="combo-3" type="text" :hide-label="true" placeholder="One Sixth" :readonly="true">Combo 3</single-line-input>
                            </one-sixth>

                            <one-third>
                                <single-line-input id="combo-3" type="text" :hide-label="true" placeholder="One Third" :readonly="true">Combo 3</single-line-input>
                            </one-third>
                        </row>

                        <row>
                            <full-width>
<preformatted>&lt;<strong>row</strong>&gt;
    &lt;<strong>one-half</strong>&gt;
        // content
    &lt;/<strong>one-half</strong>&gt;

    &lt;<strong>one-sixth</strong>&gt;
        // content
    &lt;/<strong>one-sixth</strong>&gt;

    &lt;<strong>one-third</strong>&gt;
        // content
    &lt;/<strong>one-third</strong>&gt;
&lt;/<strong>row</strong>&gt;</preformatted>
                            </full-width>
                        </row>
                    </well>

                    <well>
                        <row>
                            <one-sixth>
                                <single-line-input id="combo-8" type="text" :hide-label="true" placeholder="One Sixth" :readonly="true">Combo 8</single-line-input>
                            </one-sixth>

                            <one-sixth>
                                <single-line-input id="combo-9" type="text" :hide-label="true" placeholder="One Sixth" :readonly="true">Combo 9</single-line-input>
                            </one-sixth>

                            <one-sixth>
                                <single-line-input id="combo-10" type="text" :hide-label="true" placeholder="One Sixth" :readonly="true">Combo 10</single-line-input>
                            </one-sixth>

                            <one-half>
                                <single-line-input id="combo-11" type="text" :hide-label="true" placeholder="One Half" :readonly="true">Combo 11</single-line-input>
                            </one-half>
                        </row>

                        <row>
                            <full-width>
<preformatted>&lt;<strong>row</strong>&gt;
    &lt;<strong>one-sixth</strong>&gt;
        // content
    &lt;/<strong>one-sixth</strong>&gt;

    &lt;<strong>one-sixth</strong>&gt;
        // content
    &lt;/<strong>one-sixth</strong>&gt;

    &lt;<strong>one-sixth</strong>&gt;
        // content
    &lt;/<strong>one-sixth</strong>&gt;

    &lt;<strong>one-half</strong>&gt;
        // content
    &lt;/<strong>one-half</strong>&gt;
&lt;/<strong>row</strong>&gt;</preformatted>
                            </full-width>
                        </row>
                    </well>

                    <well>
                        <row>
                            <one-sixth>
                                <single-line-input id="combo-8" type="text" :hide-label="true" placeholder="One Sixth" :readonly="true">Combo 8</single-line-input>
                            </one-sixth>

                            <one-sixth>
                                <single-line-input id="combo-9" type="text" :hide-label="true" placeholder="One Sixth" :readonly="true">Combo 9</single-line-input>
                            </one-sixth>

                            <one-sixth>
                                <single-line-input id="combo-10" type="text" :hide-label="true" placeholder="One Sixth" :readonly="true">Combo 10</single-line-input>
                            </one-sixth>

                            <one-sixth>
                                <single-line-input id="combo-10" type="text" :hide-label="true" placeholder="One Sixth" :readonly="true">Combo 10</single-line-input>
                            </one-sixth>

                            <one-third>
                                <single-line-input id="combo-11" type="text" :hide-label="true" placeholder="One Third" :readonly="true">Combo 11</single-line-input>
                            </one-third>
                        </row>

                        <row>
                            <full-width>
<preformatted>&lt;<strong>row</strong>&gt;
    &lt;<strong>one-sixth</strong>&gt;
        // content
    &lt;/<strong>one-sixth</strong>&gt;

    &lt;<strong>one-sixth</strong>&gt;
        // content
    &lt;/<strong>one-sixth</strong>&gt;

    &lt;<strong>one-sixth</strong>&gt;
        // content
    &lt;/<strong>one-sixth</strong>&gt;

    &lt;<strong>one-sixth</strong>&gt;
        // content
    &lt;/<strong>one-sixth</strong>&gt;

    &lt;<strong>one-third</strong>&gt;
        // content
    &lt;/<strong>one-third</strong>&gt;
&lt;/<strong>row</strong>&gt;</preformatted>
                            </full-width>
                        </row>
                    </well>

                    <well>
                        <row>
                            <one-sixth>
                                <single-line-input id="combo-4" type="text" :hide-label="true" placeholder="One Sixth" :readonly="true">Combo 4</single-line-input>
                            </one-sixth>

                            <one-sixth>
                                <single-line-input id="combo-5" type="text" :hide-label="true" placeholder="One Sixth" :readonly="true">Combo 5</single-line-input>
                            </one-sixth>

                            <one-third>
                                <single-line-input id="combo-6" type="text" :hide-label="true" placeholder="One Third" :readonly="true">Combo 6</single-line-input>
                            </one-third>

                            <one-third>
                                <single-line-input id="combo-7" type="text" :hide-label="true" placeholder="One Third" :readonly="true">Combo 7</single-line-input>
                            </one-third>
                        </row>

                        <row>
                            <full-width>
<preformatted>&lt;<strong>row</strong>&gt;
    &lt;<strong>one-sixth</strong>&gt;
        // content
    &lt;/<strong>one-sixth</strong>&gt;

    &lt;<strong>one-sixth</strong>&gt;
        // content
    &lt;/<strong>one-sixth</strong>&gt;

    &lt;<strong>one-third</strong>&gt;
        // content
    &lt;/<strong>one-third</strong>&gt;

    &lt;<strong>one-third</strong>&gt;
        // content
    &lt;/<strong>one-third</strong>&gt;
&lt;/<strong>row</strong>&gt;</preformatted>
                            </full-width>
                        </row>
                    </well>
                </section>
            </container>
        </content-inner>

        <sidebar>
            <list-group>
                <div slot="title">Configurations</div>

                <list-item>
                    <a href="#full-width">Full Width</a>
                </list-item>

                <list-item>
                    <a href="#half">Half</a>
                </list-item>

                <list-item>
                    <a href="#thirds">Thirds</a>
                </list-item>

                <list-item>
                    <a href="#quarters">Quarters</a>
                </list-item>

                <list-item>
                    <a href="#fifths">Fifths</a>
                </list-item>

                <list-item>
                    <a href="#sixths">Sixths</a>
                </list-item>

                <list-item>
                    <a href="#combinations">Combinations</a>
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
        Row: use('EntradaJS/Components/Layout/Row.vue'),
        FullWidth: use('EntradaJS/Components/Layout/FullWidth.vue'),
        OneHalf: use('EntradaJS/Components/Layout/OneHalf.vue'),
        OneThird: use('EntradaJS/Components/Layout/OneThird.vue'),
        TwoThirds: use('EntradaJS/Components/Layout/TwoThirds.vue'),
        OneQuarter: use('EntradaJS/Components/Layout/OneQuarter.vue'),
        ThreeQuarters: use('EntradaJS/Components/Layout/ThreeQuarters.vue'),
        OneFifth: use('EntradaJS/Components/Layout/OneFifth.vue'),
        TwoFifths: use('EntradaJS/Components/Layout/TwoFifths.vue'),
        ThreeFifths: use('EntradaJS/Components/Layout/ThreeFifths.vue'),
        FourFifths: use('EntradaJS/Components/Layout/FourFifths.vue'),
        OneSixth: use('EntradaJS/Components/Layout/OneSixth.vue'),
        FiveSixths: use('EntradaJS/Components/Layout/FiveSixths.vue'),
        ListGroup: use('EntradaJS/Components/ListGroup.vue'),
        ListItem: use('EntradaJS/Components/ListItem.vue'),
        PageHeading: use('EntradaJS/Components/PageHeading.vue'),
        Preformatted: use('EntradaJS/Components/Preformatted.vue'),
        SingleLineInput: use('EntradaJS/Components/SingleLineInput.vue'),
        Subheading: use('EntradaJS/Components/Subheading.vue'),
        Well: use('EntradaJS/Components/Well.vue')
    };

    module.exports = {
        name: "LayoutDocumentation",

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

<style lang="scss">
    @import 'theme.scss';

    #layouts {
        .row {
            &:last-child {
                margin-top: -$spacing-xl;
            }
        }
    }
</style>
