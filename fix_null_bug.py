import re

path = r'c:\PMS\Production_Plan\resources\js\Pages\FullCalender.jsx'
with open(path, 'r', encoding='utf-8', errors='ignore') as f:
    content = f.read()

# Fix commitSnapshot
old_commit = '''  const commitSnapshot = () => {
      if (currentSnapshotRef.current) {
          setUndoStack(prev => {
              const newStack = [...prev, currentSnapshotRef.current];
              if (newStack.length > 5) newStack.shift();
              return newStack;
          });
          currentSnapshotRef.current = null;
      }
  };'''

new_commit = '''  const commitSnapshot = () => {
      const snapshotToCommit = currentSnapshotRef.current;
      if (snapshotToCommit) {
          setUndoStack(prev => {
              const newStack = [...prev, snapshotToCommit];
              if (newStack.length > 5) newStack.shift();
              return newStack;
          });
          currentSnapshotRef.current = null;
      }
  };'''

content = content.replace(old_commit, new_commit)

# Fix corrupted text
content = content.replace("Hoï¿½n tï¿½c nhï¿½p", "Hoàn tác nháp")
content = content.replace("Khï¿½ng cï¿½ thao tï¿½c kï¿½o th? nï¿½o d? hoï¿½n tï¿½c.", "Không có thao tác kéo thả nào để hoàn tác.")

# Also let's fix handleUndoFrontend to check for null just in case
old_undo = '''      const lastState = undoStack[undoStack.length - 1];
      
      calendarRef.current.getApi().batchRendering(() => {'''

new_undo = '''      const lastState = undoStack[undoStack.length - 1];
      if (!lastState || !lastState.events) {
          setUndoStack(prev => prev.slice(0, -1));
          return;
      }
      
      calendarRef.current.getApi().batchRendering(() => {'''

content = content.replace(old_undo, new_undo)

with open(path, 'w', encoding='utf-8') as f:
    f.write(content)
