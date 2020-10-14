// @flow
const generateTmpId = (salt: string = ''): string => (
  `${salt}-${Math.random().toString(36).substr(2, 9)}`
);

export default generateTmpId;
