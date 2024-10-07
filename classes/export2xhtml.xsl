<?xml version="1.0" encoding="UTF-8"?>
<!--
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.    See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.    If not, see <http://www.gnu.org/licenses/>.

 * XSLT stylesheet to transform rough XHTML derived from Word 2010 files into a more hierarchical format with divs wrapping each heading and table (question name and item)
 *
 * @package booktool_wordimport
 * @copyright 2010-2016 Eoin Campbell
 * @author Eoin Campbell
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later (5)
-->

<xsl:stylesheet
    xmlns="http://www.w3.org/1999/xhtml"
    xmlns:x="http://www.w3.org/1999/xhtml"
    xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
    xmlns:mml="http://www.w3.org/1998/Math/MathML"
    version="1.0">
    <xsl:output method="xml" encoding="UTF-8" indent="no" omit-xml-declaration="yes"/>
    <xsl:preserve-space elements="x:span x:p"/>

    <xsl:param name="debug_flag" select="1"/>
    <xsl:param name="pluginname"/>
    <xsl:param name="imagehandling"/>
    <xsl:param name="course_id"/>
    <xsl:param name="heading1stylelevel"/> <!-- Should be 1 for Glossaries and Questions, 3 for Books, Lessons and Atto -->

    <!-- Figure out an offset by which to demote headings e.g. Heading 1  to H2, etc. -->
    <!-- Use a system default, or a document-specific override -->
    <xsl:variable name="moodleHeading1Level" select="/x:html/x:head/x:meta[@name = 'moodleHeading1Level']/@content"/>
    <xsl:variable name="heading_demotion_offset">
        <xsl:choose>
        <xsl:when test="$moodleHeading1Level != ''">
            <xsl:value-of select="$moodleHeading1Level - 1"/>
        </xsl:when>
        <xsl:otherwise>
            <xsl:value-of select="$heading1stylelevel - 1"/>
        </xsl:otherwise>
        </xsl:choose>
    </xsl:variable>

    <!-- Output a newline before paras and cells when debugging turned on -->
    <xsl:variable name="debug_newline">
        <xsl:if test="$debug_flag &gt;= 1">
            <xsl:value-of select="'&#x0a;'"/>
        </xsl:if>
    </xsl:variable>

    <xsl:template match="/">
        <xsl:apply-templates/>
        <!--
        <xsl:call-template name="debugComment">
            <xsl:with-param name="comment_text" select="concat('pluginname = ', $pluginname, '; imagehandling = ', $imagehandling, '; heading1stylelevel = ', $heading1stylelevel)"/>
            <xsl:with-param name="inline" select="'true'"/>
            <xsl:with-param name="condition" select="$debug_flag &gt;= 1"/>
        </xsl:call-template>
        -->
    </xsl:template>

    <!-- Start: Identity transformation -->
    <xsl:template match="*">
        <xsl:copy>
            <xsl:apply-templates select="@*"/>
            <xsl:apply-templates/>
        </xsl:copy>
    </xsl:template>

    <xsl:template match="@*|comment()|processing-instruction()">
        <xsl:copy/>
    </xsl:template>
    <!-- End: Identity transformation -->

    <xsl:template match="text()">
        <xsl:value-of select="translate(., '&#x2009;', '&#x202f;')"/>
    </xsl:template>

    <!-- Remove empty class attributes -->
    <xsl:template match="@class[.='']"/>

    <!-- Omit superfluous MathML markup attributes -->
    <xsl:template match="@mathvariant"/>

     <!-- Delete superfluous spans that wrap the complete para content -->
    <xsl:template match="x:span[count(.//node()[self::x:span]) = count(.//node())]" priority="2"/>

    <!-- Include debugging information in the output -->
    <xsl:template name="debugComment">
        <xsl:param name="comment_text"/>
        <xsl:param name="inline" select="'false'"/>
        <xsl:param name="condition" select="'true'"/>

        <xsl:if test="boolean($condition) and $debug_flag &gt;= 1">
            <xsl:if test="$inline = 'false'"><xsl:text>&#x0a;</xsl:text></xsl:if>
            <xsl:comment><xsl:value-of select="concat('Debug: ', $comment_text)"/></xsl:comment>
            <xsl:if test="$inline = 'false'"><xsl:text>&#x0a;</xsl:text></xsl:if>
        </xsl:if>
    </xsl:template>
</xsl:stylesheet>