import styled from 'styled-components';
import { setCursorCSS } from './utils';
import { LIGHT_GREY } from '../../../../shared/colors';

export const ViewWrapper = styled.div`
  height: 100%;
  width: 100%;
  margin: 0;
  display: flex;
  box-shadow: none;
  background: ${LIGHT_GREY};
  transition: opacity 0.167s;
  opacity: 1;
  outline: none;
  user-select: none;
`;

export const GraphWrapper = styled.svg`
  align-content: stretch;
  flex: 1;
  width: 100%;
  height: 100%;
`;

export const View = styled.g`
  cursor: ${({ cursor }) => setCursorCSS(cursor)}
`;

export default ViewWrapper;
