import styled from 'styled-components';
import { DARK_BLUE } from '../../../shared/colors';

export const Container = styled.div`
  display: flex;
  flex-direction: column;
  width: 800px;
`;

export const TextContent = styled.div`
  margin-top: 5px;
`;

export const OtherContent = styled.div`
  display: flex;
  justify-content: flex-start;
  padding-bottom: 50px;
`;

export const NodeContentTitle = styled.h3`
  color: ${DARK_BLUE};
  margin-top: 10px;
  margin-bottom: 10px;
`;
