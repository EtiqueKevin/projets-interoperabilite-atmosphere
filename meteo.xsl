<?xml version='1.0' encoding="UTF-8" ?>
<xsl:stylesheet version="1.0"
    xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
    <xsl:output method="html" encoding="UTF-8" indent="yes"/>
    <xsl:strip-space elements="*"/>

    <xsl:template match="/">
        <xsl:element name="div">
            <xsl:attribute name="id">
                <xsl:text>meteo</xsl:text>
            </xsl:attribute>
            <xsl:apply-templates/>
        </xsl:element>
    </xsl:template>

    <xsl:template match="/previsions"> 
        <xsl:apply-templates select="echeance[@hour='6' or @hour='12' or @hour='18']"/>
    </xsl:template>

    <xsl:template match="echeance">
        <xsl:element name="div">
            <xsl:attribute name="class">
                <xsl:text>echeance</xsl:text>
            </xsl:attribute>

            <xsl:choose>
                <xsl:when test= "@hour = 6">
                    <xsl:element name="h1">
                        <xsl:text>Matin:</xsl:text>  
                    </xsl:element>
                </xsl:when>
                <xsl:when test= "@hour = 12">
                    <xsl:element name="h1">
                        <xsl:text>Midi:</xsl:text>  
                    </xsl:element>                
                </xsl:when>
                <xsl:when test= "@hour = 18">
                    <xsl:element name="h1">
                        <xsl:text>Soir:</xsl:text>  
                    </xsl:element>                
                </xsl:when>
            </xsl:choose>


            <xsl:element name="div">
                <xsl:attribute name="class">
                    <xsl:text>temperature</xsl:text>
                </xsl:attribute>
                <xsl:apply-templates select="temperature/level[@val='sol']"/>
            </xsl:element>

            <xsl:element name="div">
                <xsl:attribute name="class">
                    <xsl:text>nebulosite</xsl:text>
                </xsl:attribute>
                <xsl:choose>
                    <xsl:when test= "pluie &gt; 0">
                        <xsl:apply-templates select="pluie"/>
                    </xsl:when>

                    <xsl:when test= "risque_neige = 'oui'">
                        <xsl:apply-templates select="risque_neige"/>
                    </xsl:when>  

                    <xsl:otherwise>
                        <xsl:apply-templates select="nebulosite/level[@val='totale']"/>
                    </xsl:otherwise>
                </xsl:choose>
            </xsl:element>

            <xsl:element name="div">
                <xsl:attribute name="class">
                    <xsl:text>vent</xsl:text>
                </xsl:attribute>
                <xsl:apply-templates select="vent_moyen/level"/>
            </xsl:element>              
        </xsl:element>
    </xsl:template>

    <xsl:template match="temperature/level[@val='sol']">
        <xsl:value-of select="format-number(number(.) -273.15, '0.0')"/> °C
    </xsl:template>

    <xsl:template match="pluie">
        <xsl:text>🌧</xsl:text>
    </xsl:template>

    <xsl:template match="risque_neige">
        <xsl:element name="p">
            <xsl:text>🌨</xsl:text>
        </xsl:element>
    </xsl:template>

    <xsl:template match="vent_moyen/level">
        <xsl:value-of select="."/> km/h
    </xsl:template>

    <xsl:template match="nebulosite/level[@val='totale']">
        <xsl:choose>
            
            <xsl:when test=". &lt; 40">
                <xsl:text>☀️</xsl:text>

            </xsl:when>

            <xsl:when test=". &gt; 80">
                <xsl:text>☁️</xsl:text>
            </xsl:when>

            <xsl:otherwise>
                <xsl:text>⛅</xsl:text>
            </xsl:otherwise>

        </xsl:choose>
    </xsl:template>

</xsl:stylesheet>