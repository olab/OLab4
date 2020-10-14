// @flow
import type { LinkEditorModal, NodeEditorModal, SOPickerModal } from '../../components/Modals/types';

export const UPDATE_MODAL = 'UPDATE_MODAL';
type UpdateModal = {
  type: 'UPDATE_MODAL',
  modalName: string,
  modalValue: SOPickerModal
    | NodeEditorModal | LinkEditorModal,
};

export type ModalsActions = UpdateModal;

export default {
  UPDATE_MODAL,
};
