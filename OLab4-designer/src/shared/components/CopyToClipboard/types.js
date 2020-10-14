// @flow
export type ICopyToClipboardProps = {
  classes: {
    [props: string]: any,
  },
  text: string,
  medium?: boolean,
};

export type ICopyToClipboardState = {
  isCopied: boolean,
};
