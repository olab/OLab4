// @flow
export const redirectToSO = (history: any, scopedObject: string): void => history.push(`/scopedObject/${scopedObject.toLowerCase()}`);

export default {
  redirectToSO,
};
