// @flow
export type IConfirmationModalProps = {
  label: string,
  text: string,
  onClose: Function,
  showFooterButtons?: boolean,
  onSave?: Function,
  cancelBttnLabel?: string,
  saveBttnLabel?: string,
  children: any,
};
