// @flow
export type IEditorWrapperProps = {
  classes: {
    [props: string]: any,
  },
  history: any,
  children: any,
  scopedObject: string,
  onSubmit: Function,
  isEditMode: boolean,
  isDisabled: boolean,
};
