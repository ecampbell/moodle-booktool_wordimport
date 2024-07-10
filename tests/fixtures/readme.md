# How to install the Word template moodleBookStartup.dotm

This Word template contains VBA macro code and adds a new "Styles" Ribbon menu to the Word interface.
The best place to install it is as a global template in your Word Startup folder. On Windows, this is generally 
set by default to somewhere like **C:\Users\ _{yourname}_ \AppData\Roaming\Microsoft\Word\STARTUP**.
On a Mac, by default it is not set at all, but must be set to something like **Machintosh HD/Users/ _{yourname}_ /Library/Group Containers/UBF8T346G9.Office/User Content/Startup/Word**.

Note that it runs in the Microsoft Word app installed on your PC only. It does not run on the web version of Word.

## Windows PCs ##
Here are the steps to install the template in the correct location on Microsoft Windows.
1. In your web browser, download the Word template file [moodleBookStartup.dotm](./moodleBookStartup.dotm) to a folder on your PC.

2. Start Microsoft Word, choose _File > Options_ to open the Word Options dialog box,
   then select the Advanced item in the left panel, and scroll down to the **General** section in the right panel.

3. Click the "File Locations..." button to open the "File Locations" dialog box.

4. Select the "Startup" file type in the list, and click the "Modify" button to open the folder selection dialog box.
   The path is displayed in the Path field at the top of the box, but it might not all be visible to you.

5. Click on the path field, select the complete path, and copy it by pressing "\<Ctrl>+C", or right-click and choose "Copy".
   The path is all you need, so just exit the dialog box and close Word now.

6. Start Windows "File Explorer" (press the "\<Windows>+E" key combination).

7. Click the path field, paste in the path ("\<Ctrl>+V"), and press \<Return>. This opens the Startup folder.

8. Start another "File Explorer" window, go to the folder where you downloaded the template to,
    and drag and drop the file between the windows to the Startup folder.

9. Start Word again, and the "Styles" menu should now appear to the right of the "Help" menu.

## Apple Macs ##
Here are the steps to install the template in the correct location on a Mac.
1. In your web browser, download the Word template file [moodleBookStartupMac.dotm](./moodleBookStartupMac.dotm) to a folder on your Mac.

2. Start Finder, choose the "Go" menu item, press the \<Option> key to make the "Library" item in the drop-down menu visible,
   and select it to open your personal Library folder.

3. Traverse down the folder hierarchy _Group Containers > UBF8T346G9.Office > User Content > Startup > Word_ . This is where the template file
   should be stored, so copy and paste the downloaded template to here.

4. Start Microsoft Word, choose _Word > Settings_ to open the "Word Preferences" dialog box,
   and click the "File Locations" icon in the **Personal Settings** section.

5. Select "Start-up" in the "File Types" column and click the "Modify..." button to open the folder selection dialog box.

6. Click "Recents" in the left "Favourites" panel, then click the dropdown menu in the middle, and select "Word" from the list.
   Click the "Open" button to set the startup folder to this location.

7. Exit and restart Word, and the "Styles" menu should now appear as the rightmost item in the Word Ribbon menu.

