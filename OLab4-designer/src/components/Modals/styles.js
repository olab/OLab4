import styled from 'styled-components';

import { MIN_MODAL_HEIGHT, MIN_MODAL_WIDTH } from './config';
import { DARK_BLUE, WHITE } from '../../shared/colors';

const colorLightGray = 'rgb(168,168,168)';
const colorLightGrayWOpacity = 'rgb(168,168,168,0.3)';

const ModalCommonStyles = styled.div`
  min-width: ${MIN_MODAL_WIDTH}px;
  min-height: ${MIN_MODAL_HEIGHT}px;
  background-color: ${WHITE};
  color: ${WHITE};
  outline: none;
  position: absolute;
  z-index: 1200;
  font-size: 16px;
  display: flex;
  flex-direction: column;
  border-radius: 0.25rem;
  -webkit-box-shadow: 2px 2px 5px 0 ${colorLightGray};
  box-shadow: 2px 2px 5px 0 ${colorLightGray};
`;

export const ModalWrapper = styled(ModalCommonStyles)`
  left: ${({ x }) => x || 0}px;
  top: ${({ y }) => y || 0}px;
`;

export const NodeEditorWrapper = styled(ModalCommonStyles)`
  right: ${({ x }) => x || 0}px;
  bottom: ${({ y }) => y || 0}px;
  display: ${({ isShow }) => (isShow ? '' : 'none')};
`;

export const ModalHeaderButton = styled.button`
  cursor: pointer;
  background: transparent;
  border: none;
  outline: none;
  padding: 0;
`;

export const ModalHeader = styled.div`
  font-size: 32px;
  display: flex;
  color: ${DARK_BLUE};
  justify-content: flex-end;
  position: relative;
  padding: 0.5rem 1rem;
  border-bottom: 1px solid ${colorLightGrayWOpacity};
  cursor: move;

  > h4 {
    margin: 0;
    margin-right: auto;
    font-size: 20px;
  }
`;

export const ModalBody = styled.div`
  padding: 0 1rem;

  > article {
    margin: .75rem 0;
  }
`;

export const ModalFooter = styled.div`
  display: flex;
  justify-content: flex-end;
  position: relative;
  margin-top: auto;
  padding: 1rem;
  padding-top: 0;

  > button:first-child {
    margin-right: 0.5rem;
  }
`;

export const ArticleItem = styled.article`
  display: flex;
  align-items: center;
  justify-content: space-between;
`;
