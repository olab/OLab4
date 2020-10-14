import styled from 'styled-components';
import { MIDDLE_DARK_GREY } from '../../colors';

export const TextField = styled.div`
  padding: 5px;
  position: relative;
  border: 1px solid ${MIDDLE_DARK_GREY};
  border-radius: 2.5px;
  max-width: 60vw;
  max-height: 250px;
  overflow: auto;
`;

export default {
  TextField,
};
