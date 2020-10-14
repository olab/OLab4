// @flow
const removeHTMLTags = (text: string): string => {
  const regex = /(<([^>]+)>)/ig;
  const result = text.replace(regex, '');

  return result;
};

export default removeHTMLTags;
