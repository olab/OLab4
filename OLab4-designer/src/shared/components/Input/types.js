// @flow
export type IInputProps = {
  name: string,
  label: string,
  variant: string,
  errorText: string,
  placeholder: string,
  disabled: boolean,
  fullWidth: boolean,
  autoFocus: boolean,
  autoComplete: boolean,
};

export type IInputState = {
  value: string,
  standardErrorMessage: string,
  isShowError: boolean,
};
