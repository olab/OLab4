// @flow
export type ModalPosition = {
  x: number,
  y: number,
};

export type LinkEditorModal = {
  ...ModalPosition,
};

export type NodeEditorModal = {
  ...ModalPosition,
};

export type SOPickerModal = {
  ...ModalPosition,
  isShow: boolean,
};

export type Modals = {
  [modalName: string]: SOPickerModal |
    LinkEditorModal | NodeEditorModal,
};
