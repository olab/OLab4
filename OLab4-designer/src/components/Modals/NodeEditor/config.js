export const EDITOR_OPTIONS = {
  toolbar: 'styleselect | bold italic strikethrough | image link | bullist numlist',
  style_formats: [
    {
      title: 'Headings',
      items: [
        { title: 'Heading 1', format: 'h1' },
        { title: 'Heading 2', format: 'h2' },
        { title: 'Heading 3', format: 'h3' },
        { title: 'Heading 4', format: 'h4' },
        { title: 'Heading 5', format: 'h5' },
        { title: 'Heading 6', format: 'h6' },
      ],
    },
    {
      title: 'Align',
      items: [
        { title: 'Left', format: 'alignleft' },
        { title: 'Center', format: 'aligncenter' },
        { title: 'Right', format: 'alignright' },
        { title: 'Justify', format: 'alignjustify' },
      ],
    },
  ],
  statusbar: false,
};

export default {
  EDITOR_OPTIONS,
};
