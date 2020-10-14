import styled from 'styled-components';

import { WHITE } from '../../colors';

export const ModalContainer = styled.div`
  position: absolute;
  background-color: ${WHITE};
  outline: none;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  box-shadow: 0px 3px 5px -1px rgba(0, 0, 0, .2),
    0px 5px 8px 0px rgba(0, 0, 0, .14),
    0px 1px 14px 0px rgba(0, 0, 0, .12);
`;

export const ModalWrapper = styled.div`
  position: relative;
  padding: 2rem;
`;

export const ModalFooter = styled.div`
  display: flex;
  justify-content: flex-end;
  margin-top: 2rem;

  > button:first-of-type {
    margin-right: .5rem;
  }
`;

export const CrossButtonWrapper = styled.div`
  position: absolute;
  top: 10px;
  right: 10px;
  cursor: pointer;
`;
